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
    protected static $_bundlePath = 'desktop.bundles/index';
    protected static $_bundleName = 'index';

    public static function setBundle($bundlePath = 'desktop.bundles/index', $bundleName = 'index')
    {
        self::$_bundlePath = $bundlePath;
        self::$_bundleName = $bundleName;
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

    public static function send()
    {
        $client = new \Zend_Http_Client('http://localhost:3333/' . self::$_bundlePath);
        self::set('bundle', array('name' => self::$_bundleName, 'path' => self::$_bundlePath));
        $client->setParameterPost(self::$_data);
        $response = $client->request('POST');
        return $response->getBody();
    }

}