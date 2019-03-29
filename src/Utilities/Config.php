<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Utilities;

class Config
{
    private static $data = null;
    protected static $instance = null;
    
    public function __toString()
    {
        return (string)self::$data;
    }
    
    /**
     * clone
     * Kopieren der Instanz von aussen ebenfalls verbieten
     */
    protected function __clone()
    {
    }
    
    /**
     * constructor
     * externe Instanzierung verbieten
     */
    protected function __construct()
    {
    }
    
    /**
     * @param string $file
     *
     * @return Config|null
     */
    public static function getInstance($file = CONNECTOR_DIR . '/config/config.json')
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        
        if (is_null(self::$data)) {
            self::$data = json_decode(@file_get_contents($file));
            if (is_null(self::$data)) {
                self::$data = new \stdClass();
            }
        }
        
        return self::$instance;
    }
    
    public static function getData(){
        self::getInstance();
        
        return self::$data;
    }
    
    /**
     * @param $name
     * @param $value
     */
    public static function set($name, $value)
    {
        self::getInstance();
        self::$data->$name = $value;
        self::save();
    }
    
    /**
     * @param $name
     *
     * @return bool
     */
    public static function has($name)
    {
        self::getInstance();
        
        return array_key_exists($name, (array)self::$data);
    }
    
    /**
     * @param $name
     *
     * @return mixed
     */
    public static function get($name)
    {
        self::getInstance();
        
        return self::$data->$name;
    }
    
    /**
     * @param $name
     *
     * @return mixed
     */
    public static function remove($name)
    {
        self::getInstance();
        if (self::has($name)) {
            unset(self::$data->$name);
            
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @return bool
     */
    public static function save()
    {
        self::getInstance();
        if (file_put_contents(CONNECTOR_DIR . '/config/config.json', json_encode(self::$data)) === false) {
            return false;
        } else {
            return true;
        }
    }
}
