<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
/** Singleton class **/
Class Application_Repository
{
    private static $_thisInstance;
    
    /** Handler Properties **/
    protected static $_registry;
    protected static $_session;
    protected static $_request;
    protected static $_functions;
    protected static $_static;
    
    /** Other Properties **/
    protected static $_underscoreCache = array();
    protected static $_camelizeCache = array();
    
    
    private function __construct()
    {
        self::$_registry = array();
        
        self::$_request = App::getSingleton('Application_Request');
        
        self::$_session = App::getSingleton('Application_SessionHandler');
        
        self::$_functions = App::getSingleton('Application_Functions');
        
        self::$_static = App::getSingleton('Application_StaticData');
    }
    
    
    private function __clone() { }
    
    
    public static function singleton()
    {
        if (!isset(self::$_thisInstance))
        {
            $_class = __CLASS__;
            self::$_thisInstance = new $_class;
        }
        
        return self::$_thisInstance;
    }
    
    
    public function get($key)
    {
        // Try registry:
        if (!is_null($_value = $this->getData($key))) return $_value;
        
        // Try session:
        if (!is_null($_value = $this->getSession($key))) return $_value;
        
        return NULL;
    }
    
    
    public function set($key, $value = NULL, $session = false)
    {
        if (!$session) $this->setData($key, $value);
        
        if ($session) $this->setSession($key, $value);
    }
    
    
    public function delete($key, $session = false)
    {
        if ($session)
        {
            self::$_session->delete($key);
        }
        else
        {
            unset(self::$_registry[$key]);
        }
    }
    
    
    public function getData($key)
    {
        if (isset(self::$_registry[$key])) return self::$_registry[$key];
        
        return NULL;
    }
    
    
    public function setData($key, $value = NULL)
    {
        self::$_registry[$key] = $value;
    }
    
    
    public function newClass($key, $className, $session = false)
    {
        $className = $this->clean($className, TYPE::_STR);
        
        if (!$session AND !isset(self::$_registry[$key]) AND self::$_session->get($key) === NULL) self::$_registry[$key] = App::getSingleton($className);
        
        if ($session AND self::$_session->get($key) === NULL AND !isset(self::$_registry[$key])) self::$_session->set($key, App::getSingleton($className));
    }
    
    
    public function getSession($key)
    {
        return self::$_session->get($key);
    }
    
    
    public function setSession($key, $value = NULL)
    {
        self::$_session->set($key, $value);
    }
    
    
    public function GPCExists($key)
    {
        return self::$_request->GPCExists($key);
    }
    
    
    public function getRequest($key)
    {
        return self::$_request->get($key);
    }
    
    
    public function clean($param1, $param2 = NULL, $param3 = NULL)
    {
        return self::$_request->clean($param1, $param2, $param3);
    }
    
    
    public function underscore($string)
    {
        return $this->_underscore($string);
    }
    
    
    protected function _underscore ($string)
    {
        if (isset(self::$_underscoreCache[$string]))
        {
            return self::$_underscoreCache[$string];
        }
        
        $_result = strtolower(preg_replace('/(.)([A-Z][^A-Z])/', "$1_$2", $string));
        
        self::$_underscoreCache[$string] = $_result;
        
        return $_result;
    }
    
    
    public function camelize($string)
    {
        return $this->_camelize($string);
    }
    
    
    protected function _camelize ($string)
    {
        if (isset(self::$_camelizeCache[$string]))
        {
            return self::$_camelizeCache[$string];
        }
        
        $_result = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        
        self::$_camelizeCache[$string] = $_result;
        
        return $_result;
    }
    
    
    public function pageString(Array $array)
    {
        return implode('/', array_map(array($this, '_underscore'), $array));
    }
    
    
    public function pageArray($string, $validate = true)
    {
        $_default = array(
            'view' => 'Public',
            'path' => 'Default',
            'page' => 'Home',
        );
        
        $_page = array(
            'view' => '',
            'path' => '',
            'page' => '',
        );
        
        $_parts = explode('/', $string);
        
        if (isset($_parts[0]) AND !empty($_parts[0])) $_page['view'] = $this->_camelize($this->clean($_parts[0], TYPE::_STR));
        
        if (isset($_parts[1]) AND !empty($_parts[1])) $_page['path'] = $this->_camelize($this->clean($_parts[1], TYPE::_STR));
        
        if (isset($_parts[2]) AND !empty($_parts[2])) $_page['page'] = $this->_camelize($this->clean($_parts[2], TYPE::_STR));
        
        if ($validate)
        {
            return ($this->validatePageArray($_page)) ? $_page : $_default;
        }
        
        return $_page;
    }
    
    
    public function validatePageArray(Array $page)
    {
        if (empty($page['view']) OR empty($page['path']) OR empty($page['page'])) return false;
        
        if (!is_dir(BASE_PATH . DS . 'Module' . DS . $page['view'] . DS . $page['path'])) return false;
        
        return true;
    }
    
    
    public function getContext($key)
    {
        return $this->getSession('user')->getContext($key);
    }
    
    
    public function setContext($key, $value)
    {
        $this->getSession('user')->setContext($key, $value);
    }
    
    
    public function debug($value, $message = '', $printOutput = true)
    {
        $_html = '<pre>' . ($message ? htmlentities($message) . PHP_EOL : '') . htmlentities(print_r($value, true)) . PHP_EOL . '</pre>';
        
        if ($printOutput)
        {
            echo $_html;
        }
        else
        {
            return $_html;
        }
    }
    
    
    
    /** Overloading methods for data handeling **/
    public function __call($method, Array $arguments = array())
    {
        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this, $method), $arguments);
        }
        
        if (method_exists('App', $method))
        {
            return call_user_func_array(array('App', $method), $arguments);
        }
        
        if (method_exists(self::$_functions, $method))
        {
            return call_user_func_array(array(self::$_functions, $method), $arguments);
        }
        
        if (method_exists(self::$_static, $method))
        {
            return call_user_func_array(array(self::$_static, $method), $arguments);
        }
        
        if (strlen($method) > 3)
        {
            $_prefix = substr($method, 0, 3);
            
            $_name = substr($method, 3);
            
            switch (strtolower($_prefix))
            {
                case 'get':
                    array_unshift($arguments, $this->_underscore($_name));
                    
                    return call_user_func_array(array($this, 'get'), $arguments);
                    
                case 'set':
                    array_unshift($arguments, $this->_underscore($_name));
                    
                    return call_user_func_array(array($this, 'set'), $arguments);
                    
                case 'new':
                    array_unshift($arguments, $this->_underscore($_name));
                    
                    return call_user_func_array(array($this, 'newClass'), $arguments);
            }
        }
        
        throw new Exception('Method ' . $method . ' does not exist');
    }
    
    public function __get($key)
    {
        return $this->get($this->_underscore($key));
    }
    
    public function __set($key, $value = NULL)
    {
        $this->set($this->_underscore($key), $value);
    }
    
    public function __unset($key)
    {
        $this->delete($this->_underscore($key));
    }
    
    public function __isset($key)
    {
        return isset(self::$_registry[$this->_underscore($key)]);
    }
}

