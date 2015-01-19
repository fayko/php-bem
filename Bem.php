<?php
/**
 * Created by PhpStorm.
 * User: Yuri Fayko
 * Date: 14/11/13
 * Time: 12:07
 */

class Bem
{
    private static $_instances = array();

    protected static $_data = array();
    protected static $_bundleRootPath = '';
    protected static $_bundlePath = '';
    protected static $_bundle = 'desktop';
    protected static $_bundleName = 'index';
    protected static $_host = 'localhost';
    protected static $_port = '3333';
    protected static $_bundleSuffix = '.bundles';
    protected static $_publicPath = '';

    protected $_multiCurlResponse = array();

    /**
     * @return Bem
     */
    public static function getInstance()
    {
        // Get name of current class
        $sClassName = get_called_class();

        // Create new instance if necessary
        if (! isset(self::$_instances[$sClassName])) {
            self::$_instances[$sClassName] = new $sClassName();
        }

        return self::$_instances[$sClassName];
    }

    /**
     * Private final clone method
     */
    final private function __clone ()
    {}

    /**
     * @param $path
     * @return $this
     */
    public function setBundleRoot($path)
    {
        self::$_bundleRootPath = $path;
        return $this;
    }

    /**
     * @param $bundle
     * @param string $bundleName
     * @return $this
     */
    public function setBundle($bundle, $bundleName = 'index')
    {
        self::$_bundle = $bundle;
        self::$_bundleName = $bundleName;
        self::$_bundlePath = self::$_bundleRootPath . '/' . $bundle . self::$_bundleSuffix .'/' . $bundleName;

        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function setHost($host)
    {
        self::$_host = (string)$host;
        return $this;
    }

    /**
     * @param $port
     * @return $this
     */
    public function setPort($port)
    {
        self::$_port = (int)$port;
        return $this;
    }

    public function setPublicPath($path)
    {
        self::$_publicPath = $path;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return $this
     */
    public function set($key, $data)
    {
        self::$_data[$key] = $data;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return $this
     */
    public function setGlobal($key, $data)
    {
        if(!isset(self::$_data['globals'])) {
            self::$_data['globals'] = array();
        }
        self::$_data['globals'][$key] = $data;
        return $this;
    }

    public function addBlock($name, $params = array())
    {
        if(!isset(self::$_data['blocks'])) {
            self::$_data['blocks'] = array();
        }

        self::$_data['blocks'][$name] = $params;
        return $this;
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get($key)
    {
        if(isset(self::$_data[$key])) {
            return self::$_data[$key];
        } else {
            return false;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function is($key)
    {
        if(isset(self::$_data[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public function getData()
    {
        return self::$_data;
    }

    public function getBundlePath()
    {
        return self::$_bundlePath;
    }

    public function getBundleName()
    {
        return self::$_bundle . self::$_bundleSuffix . '/' . self::$_bundleName;
    }

    public function getFullAddress()
    {
        return 'http://' . self::$_host . ':' . self::$_port . '/' . ltrim(self::getBundlePath(), '/');
    }

    public function getResourceLink($extension)
    {
        return self::$_publicPath .'/' . $this->getBundleName() . '/_' . self::$_bundleName . '.' . $extension;
    }

    public function getCssLink()
    {
        return $this->getResourceLink('css');
    }

    public function getJsLink()
    {
        return $this->getResourceLink('js');
    }

    public function send()
    {
        $client = new \Zend_Http_Client(self::getFullAddress());
        self::set('bundle', array('name' => self::getBundleName(), 'path' => self::getBundlePath()));
        $json = Zend_Json::encode(self::$_data);
        $client->setParameterPost(array('json_data' => $json));
        $response = $client->request('POST');
        $content = $response->getBody();

        if($response->getHeader('Content-Type') == 'application/json') {
            $content = Zend_Json::decode($content);
        }

        return $content;
    }

    /**
     * Отправить параллельно несколько запросов к ноде
     * @param array $requestList массив запросов - в каждом должна быть
     *    data - отправляемы данные,
     *    key_in_response - какой будет ключ в массиве ответов,
     *    port - не обязательный параметр - на какой порт отправлять запрос,
     * @return array
     */

    public function multiSend($requestList)
    {
        $curlOpts = array(
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            //Заголовок, чтобы избежать промежутного ответа с кодом 100
            CURLOPT_HTTPHEADER     => array('Expect:'),
            CURLOPT_POST           => true
        );


        $curl = new Bem_Curl_Multi();
        self::set('bundle', array('name' => self::getBundleName(), 'path' => self::getBundlePath()));

        foreach ($requestList as $request) {
            $this->set('page', $request['data']);
            if (!empty($request['port'])) {
                $this->setPort($request['port']);
            }
            $json = Zend_Json::encode(self::$_data);
            $curlOpts[CURLOPT_POSTFIELDS] = array('json_data' => urlencode($json));
            $curl->addTread(self::getFullAddress(), array($this, '_parseMultiCurlResponse'), array('key_in_response' => $request['key_in_response']), $curlOpts);
        }
        $curl->request();
        return $this->_getMultiCurlResponse();
    }

    /**
     * Это колбек, который вызывается при возврате одного из ответа мульти курл запроса
     * @param Bem_Curl $curl
     * @param $url
     * @param $keyInResponse
     */
    public function _parseMultiCurlResponse(Bem_Curl $curl, $url, $keyInResponse)
    {
        $content = $curl->getBody();
        if ($curl->getHeader('Content-Type') == 'application/json') {
            $content = Zend_Json::decode($content);
        }
        $this->_multiCurlResponse[$keyInResponse] = $content;
    }

    private function _getMultiCurlResponse()
    {
        return $this->_multiCurlResponse;
    }





}