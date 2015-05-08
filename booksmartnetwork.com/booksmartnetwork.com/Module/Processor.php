<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_Processor Extends Application_View
{
    public function process()
    {
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        if (method_exists("Module_{$_view}_{$_path}_Processor", 'process' . $this->_currentModule['page']))
        {
            call_user_func(array($this, 'process' . $this->_currentModule['page']));
        }
        
        return $this;
    }
}

