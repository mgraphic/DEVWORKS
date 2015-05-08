<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_Config
{
    protected static $_configData = array();
    
    
    public function __construct($configModule)
    {
        if (empty($configModule)) throw new Exception(__CLASS__ . '::__construct() requires a non empty $configModule string');
        
        if (!isset(self::$_configData[$configModule]))
        {
            $_xml = App::getInstance('Lib_XMLParser');
            
            $_path = BP . DS . str_replace('_', DS, $configModule) . DS . 'config.xml';
            
            self::$_configData[$configModule] = $_xml->parse($_path);
        }
    }
    
    
    public function getConfig($xmlPath)
    {
        reset(self::$_configData);
        
        while (($_config = key(self::$_configData)) !== NULL)
        {
            if (self::$_configData[$_config]->exists($xmlPath))
            {
                return self::$_configData[$_config]->get($xmlPath);
            }
            
            next(self::$_configData);
        }
        
        return NULL;
    }
}

