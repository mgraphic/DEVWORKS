<?php
  
  if (strtolower(basename($_SERVER['PHP_SELF'])) == strtolower(basename(__FILE__))) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Final Class Config
{
    public function __construct()
    {
        ini_set('arg_separator.output', '&');
        
        // Set includes token
        define('__APP', true);
        
        define('BASE_PATH', realpath(dirname(dirname(__FILE__))));
        
        define('PUBLIC_ROOT', str_replace('//', '/', dirname($_SERVER['PHP_SELF']) . '/'));
        
        define('DS', DIRECTORY_SEPARATOR);
        define('PS', PATH_SEPARATOR);
        define('BP', BASE_PATH);
        define('PR', PUBLIC_ROOT);
        
        $this->_include('cfg_EXCEPTIONCODES.php');
        
        $this->_include('cfg_STATUSCODES.php');
        
        $this->_include('cfg_TYPE.php');
    }
    
    private function _include($inc)
    {
        require_once dirname(realpath(__FILE__)) . DS . $inc;
    }
}


Final Class App
{
    /** Handler Properties **/
    protected static $_registry;
    protected static $_config;
    protected static $_controller;
    
    /** Other Properties **/
    protected static $_singleton = array();
    
    
    public static function init()
    {
        static $_init = false;
        
        if (!$_init)
        {
            new Config;
            
            spl_autoload_register(array('self', '_autoloader'));
            
            set_exception_handler(array('Application_ExceptionHandler', 'handleException'));
            
            self::$_config = self::getInstance('Application_Config', array('Application'));
            
            $_includePath = self::getConfig('php_include_path');
            
            $_includePath = explode(PS, $_includePath);
            
            if (self::getConfig('php_include_path_append') !== false) $_includePath = array_merge(explode(PS, get_include_path()), $_includePath);
            
            set_include_path(implode(PS, $_includePath));
            
            self::$_registry = self::getSingleton('Application_Repository');
            
            self::$_registry->setUri(substr(reset(explode('?', $_SERVER['REQUEST_URI'])), strlen(PUBLIC_ROOT)));
            
            self::$_registry->setProtocal(@(strtolower($_SERVER['HTTPS']) == 'on' OR $_SERVER['HTTPS'] == '1' OR strstr(strtoupper($_SERVER['HTTP_X_FORWARDED_BY']),'SSL') OR strstr(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']),'SSL'))  ? 'SSL' : 'NONSSL');
            
            self::getSingleton('Application_User');
#            #var_dump(self::$_registry->getSession('user'));
#            if (!self::$_registry->getSession('user'))
#            {
#                self::$_registry->setSession('user', self::getSingleton('Application_User'));
#            }
            
            self::$_controller = self::getSingleton('Application_Controller');
            
            $_init = true;
        }
    }
    
    
    public static function run($runController = true)
    {
        static $_init = false;
        
        if (!$_init)
        {
            try
            {
                self::init();
                
                if ($runController) self::$_controller->run();
            }
            catch (Exception $e)
            {
                throw new Exception($e->getMessage(), EXCEPTIONCODES::OTHER);
                echo $e->getMessage();
                exit;
            }
            
            $_init = true;
        }
    }
    
    
    public static function getRegistry()
    {
        return self::$_registry;
    }
    
    
    public static function getPath($string, $includeBasePath = true)
    {
        if ($includeBasePath) return BP . DS . str_replace('_', DS, $string);
        
        return str_replace('_', DS, $string);
    }
    
    
    public static function getCurrentStack()
    {
        return (self::$_controller) ? self::$_controller->getCurrentStack() : array();
    }
    
    
    public static function getPreviousStack()
    {
        return (self::$_controller) ? self::$_controller->getPreviousStack() : array();
    }
    
    
    public static function getInstance($class, Array $arguments = array())
    {
        $_refClass = new ReflectionClass($class);
        
        if ($_refClass->hasMethod('singleton'))
        {
            return call_user_func_array(array($class, 'singleton'), $arguments);
        }
        
        return $_refClass->newInstanceArgs($arguments);
    }
    
    
    public static function getSingleton($class, Array $arguments = array())
    {
        if (isset(self::$_singleton[$class]))
        {
            return self::$_singleton[$class];
        }
        
        self::$_singleton[$class] = self::getInstance($class, $arguments);
        
        return self::$_singleton[$class];
    }
    
    
    public static function getData($key)
    {
        return self::$_registry->getData($key);
    }
    
    
    public static function getRequest($key)
    {
        return self::$_registry->getRequest($key);
    }
    
    
    public static function GPCExists($key)
    {
        return self::$_registry->GPCExists($key);
    }
    
    
    public static function getSession($key)
    {
        return self::$_registry->getSession($key);
    }
    
    
    public static function getConfig($config)
    {
        return self::$_config->getConfig($config);
    }
    
    
    public static function clean($param1, $param2 = NULL, $param3 = NULL)
    {
        return self::$_registry->clean($param1, $param2, $param3);
    }
    
    
    public static function errorView($message = NULL, $statusCode = NULL)
    {
        self::$_controller->errorView($message, $statusCode);
    }
    
    
    public static function redirect($url)
    {
        self::$_controller->redirect($url);
    }
    
    
    public static function classExists($className, $autoload = true)
    {
        if (class_exists($className, false)) return true;
        
        $_file = self::getPath($className, false) . '.php';
        
        if ($className AND self::fileExists($_file))
        {
            if ($autoload) return self::_autoloader($className);
            
            return true;
        }
        
        return false;
    }
    
    
    public static function fileExists($file)
    {
        if (!file_exists($file))
        {
            $_paths = explode(PS, get_include_path());
            
            foreach ($_paths AS $_path)
            {
                if (file_exists($_path . DS . preg_replace('%^' . str_replace("\\", "\\\\", BP . DS) . '%', '', $file))) return true;
            }
            
            return false;
        }
        
        return true;
    }
    
    
    private static function _autoloader($className)
    {
        if (class_exists($className, false)) return false;
        
        $_file = self::getPath($className, false) . '.php';
        
        if ($className AND self::fileExists($_file))
        {
            require_once $_file;
        }
        else
        {
            throw new Exception("File for $className does not exists: $_file", Lib_Helper_ExceptionCodes::APP_NOFILE);
            
            return false;
        }
        
        $_name = explode('_', $className);
        
        $_isInterface = (substr(end($_name), 0, 1) == 'i') ? true : false;
        
        if (!$_isInterface AND !class_exists($className, false))
        {
            throw new Exception("Class for $className does not exists", Lib_Helper_ExceptionCodes::APP_NOCLASS);
            
            return false;
        }
        else if ($_isInterface AND !interface_exists($className, false))
        {
            throw new Exception("Interface for $className does not exists", Lib_Helper_ExceptionCodes::APP_NOINTERFACE);
            
            return false;
        }
        
        return true;
    }
}
  
  
  if (!function_exists('get_called_class'))
  {
      function get_called_class($backtrace = false, $level = 1)
      {
          if (!$backtrace) $backtrace = debug_backtrace();
          
          if (!isset($backtrace[$level])) throw new Exception('Cannot find called class: Stack level too deep');
          
          if (!isset($backtrace[$level]['type'])) throw new Exception ('Backtrace method type not set');
          
          switch ($backtrace[$level]['type'])
          {
              case '::':
                  $lines = file($backtrace[$level]['file']);
                  
                  $i = 0;
                  
                  $callerLine = '';
                  
                  do
                  {
                      $i++;
                      
                      $callerLine = $lines[$backtrace[$level]['line'] - $i] . $callerLine;
                  }
                  while (stripos($callerLine, $backtrace[$level]['function']) === false);
                  
                  preg_match('/([a-zA-Z0-9\_]+)::' . $backtrace[$level]['function'] . '/', $callerLine, $matches);
                  
                  if (!isset($matches[1]))
                  {
                      throw new Exception ('Could not find caller class: Originating method call is obscured');
                  }
                  
                  switch ($matches[1])
                  {
                      case 'self':
                      case 'parent':
                          return get_called_class($backtrace, ++$level);
                      default:
                          return $matches[1];
                  }
                  
              case '->':
                  switch ($backtrace[$level]['function'])
                  {
                      case '__get':
                          if (!is_object($backtrace[$level]['object'])) throw new Exception ('Edge case fail: __get called on non object');
                          
                          return get_class($backtrace[$level]['object']);
                      default:
                          return $backtrace[$level]['class'];
                  }
                  
              default:
                  throw new Exception ('Unknown backtrace method type');
          }
      }
  }

