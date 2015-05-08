<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Abstract Class Application_Extender
{
    protected $_registry;
    
    protected $_session;
    
    protected $_outputString = '';
    
    protected $_vars = array();
    
    
    public function __construct()
    {
        $this->_registry = App::getSingleton('Application_Repository');
        
        $this->_session = App::getSingleton('Application_SessionHandler');
    }
    
    
    public function getPath($__path_, $__ext_ = 'php', $__bufferOutput_ = false, $__includeOnce_ = false)
    {
        $___path_ = App::getPath($__path_) . '.' . $__ext_;
        
        if (is_readable($___path_))
        {
            if ($__bufferOutput_) ob_start();
            
            if ($__includeOnce_)
            {
                include_once $___path_;
            }
            else
            {
                include $___path_;
            }
            
            if ($__bufferOutput_)
            {
                $this->_outputString = ob_get_contents();
                
                ob_end_clean();
            }
        }
        
        return $this;
    }
    
    
    public function getHtml($path, $bufferOutput = false)
    {
        return $this->getPath($path, 'phtml', $bufferOutput);
    }
    
    
    public function getForm($path)
    {
        $_path = explode('_', $path);
        
        $_name = 'get' . end($_path);
        
        if (!$this->$_name()) $this->getPath($path);
        
        return $this->$_name();
    }
    
    
    public function toHtml($param1 = NULL, $param2 = NULL, $param3 = NULL, $param4 = NULL)
    {
        $_flags = ENT_COMPAT;
        
        $_charSet = 'UTF-8';
        
        $_doubleEncode = true;
        
        if (is_string($param1))
        {
            $_string = $param1;
            
            if (is_int($param2)) $_flags = $param2;
            
            if (is_string($param3)) $_charSet = $param3;
            
            if (is_bool($param4)) $_doubleEncode = $param4;
        }
        else
        {
            $_string = (string)$this->_outputString;
            
            if (is_int($param1)) $_flags = $param1;
            
            if (is_string($param2)) $_charSet = $param2;
            
            if (is_bool($param3)) $_doubleEncode = $param3;
        }
        
        return htmlentities($_string, $_flags, $_charSet, $_doubleEncode);
    }
    
    
    public function setVar($name, &$value)
    {
        $this->_vars[$name] =& $value;
        
        return $value;
    }
    
    
    public function getVar($name)
    {
        return $this->_vars[$name];
    }
    
    
    public function unsetVar($name)
    {
        unset($this->_vars[$name]);
        
        return $this;
    }
    
    
    
    public function __call($method, Array $arguments = array())
    {
        return $this->_registry->__call($method, $arguments);
    }
    
    public function __get($name)
    {
        return $this->_registry->__get($name);
    }
    
    public function __set($name, $value)
    {
        return $this->_registry->__set($name, $value);
    }
    
    public function __unset($name)
    {
        return $this->_registry->__unset($name);
    }
    
    public function __isset($name)
    {
        return $this->_registry->__isset($name);
    }
    
    public function __toString()
    {
        return (string)$this->_outputString;
    }
}

