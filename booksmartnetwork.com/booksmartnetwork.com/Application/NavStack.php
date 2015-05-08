<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_NavStack Extends Application_Extender
{
    protected $_stack;
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_loadStack();
    }
    
    
    protected function _loadStack()
    {
        $this->_stack = $this->getStack();
        
        if (!$this->_stack) $this->_stack = array();
    }
    
    
    protected function _saveStack()
    {
        $this->setStack($this->_stack, true);
    }
    
    
    public function saveTemp(Array $route)
    {
        $this->setTempStack($route, true);
    }
    
    
    public function getTemp()
    {
        return $this->getTempStack();
    }
    
    
    public function getPrevious()
    {
        return (array)end($this->_stack);
    }
    
    
    public function setNext(Array $route)
    {
        $this->_stack[] = $route;
        
        $this->setTempStack(NULL, true);
    }
    
    
    public function get($id)
    {
        return (isset($this->_stack[(int)$id])) ? $this->_stack[(int)$id] : array();
    }
    
    
    public function __destruct()
    {
        $this->_saveStack();
    }
}

