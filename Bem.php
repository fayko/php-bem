<?php
/**
 * Created by PhpStorm.
 * User: Yuri Fayko
 * Date: 14/11/13
 * Time: 12:07
 */

class Bem
{
    protected static $_data = array();
    protected static $_bundleRootPath = '';
    protected static $_bundlePath = '';
    protected static $_bundle = 'desktop';
    protected static $_bundleName = 'index';
    protected static $_host = 'localhost';
    protected static $_port = '3333';
    protected static $_bundleSufix = '.bundle';

    public static function setBundleRoot($path)
    {
        self::$_bundleRootPath = $path;
    }

    public static function setBundle($bundle, $bundleName = 'index')
    {
        self::$_bundle = $bundle;
        self::$_bundleName = $bundleName;
        self::$_bundlePath = self::$_bundleRootPath . '/' . $bundle . self::$_bundleSufix .'/' . $bundleName;

    }

    public static function setHost($host)
    {
        self::$_host = $host;
    }

    public static function setPort($port)
    {
        self::$_port = $port;
    }

    /**
     * @param string $key
     * @param mixed $data
     */
    public static function set($key, $data)
    {
        self::$_data[$key] = $data;
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public static function get($key)
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
    public static function is($key)
    {
        if(isset(self::$_data[$key])) {
            return true;
        } else {
            return false;
        }
    }

    public static function getData()
    {
        return self::$_data;
    }

    public static function getBundlePath()
    {
        return self::$_bundlePath;
    }

    public static function getBundleName()
    {
        return self::$_bundle . self::$_bundleSufix . '/' . self::$_bundleName;
    }

    public static function getFullAddress()
    {
        return 'http://' . self::$_host . ':' . self::$_port . '/' . ltrim(self::getBundlePath(), '/');
    }


    public static function send()
    {
        $client = new \Zend_Http_Client(self::getFullAddress());
        self::set('bundle', array('name' => self::getBundleName(), 'path' => self::getBundlePath()));
        $client->setParameterPost(self::$_data);
        $response = $client->request('POST');
        return $response->getBody();
    }

}