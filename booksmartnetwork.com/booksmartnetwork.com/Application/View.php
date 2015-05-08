<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_View Extends Application_Extender
{
    protected $_currentStack;
    protected $_previousStack;
    protected $_currentModule;
    protected $_previousModule;
    
    protected $_messages = array();
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_currentStack = App::getCurrentStack();
        
        $this->_previousStack = App::getPreviousStack();
        
        $this->_currentModule = $this->pageArray($this->_currentStack['module'], false);
        
        if ($this->validatePageArray($this->_currentModule))
        {
            App::getInstance('Application_Config', array("Module_{$this->_currentModule[view]}_{$this->_currentModule[path]}"));
        }
        
        $this->_previousModule = $this->pageArray($this->_previousStack['module'], false);
        
        if ($this->validatePageArray($this->_previousModule))
        {
            App::getInstance('Application_Config', array("Module_{$this->_previousModule[view]}_{$this->_previousModule[path]}"));
        }
        
        $this->setVar('action', $this->_currentStack['action']);
    }
    
    
    public function getMessages()
    {
        return $this->_messages;
    }
    
    
    protected function _setMessage($message, $parent = 'default', $redirect = false, $statusCode = 0)
    {
        $this->_messages[] = array(
            'message' => (string)$message,
            'parent' => (string)$parent,
            'redirect' => $redirect,
            'status_code' => (int)$statusCode,
        );
    }
    
    
    protected function _setAuthorized($roles)
    {
        if (!$this->getSession('user')->hasRole($roles))
        {
            throw new Exception('You are not authorized to view this page', EXCEPTIONCODES::NOT_AUTHORIZED);
        }
    }
}

