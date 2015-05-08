<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_Public_Default_Service Extends Module_Service Implements Module_Helper_iService
{
    public function serviceTest()
    {
        $this->_service = array(
            
            'url' => 'http://localhost/booksmartnetwork.com/service.php?a=b&b=a',
            
            'method' => 'post',
            
            'params' => array(
                'this' => 'that',
                'that' => 'this',
            ),
            
            'as_user' => true,
        );
    }
}

