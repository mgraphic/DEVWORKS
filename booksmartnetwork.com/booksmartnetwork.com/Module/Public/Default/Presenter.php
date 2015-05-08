<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_Public_Default_Presenter Extends Module_Presenter Implements Module_Helper_iPresenter
{
    public function pageHome(){}
    
    
    public function pageError()
    {
        $this->_error = $this->_currentStack['status'];
    }
}

