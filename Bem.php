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

    public function addBlock($name, $block, $params = array())
    {
        if(!isset(self::$_data['blocks'])) {
            self::$_data['blocks'] = array();
        }
        $params['block'] = $block;
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

}