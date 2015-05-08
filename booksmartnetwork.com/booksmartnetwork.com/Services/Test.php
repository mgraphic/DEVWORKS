<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Services_Test Extends Application_Extender
{
    public function __construct()
    {
        parent::__construct();
        
        #sleep(10);
        
        $array = array(
            
            'REQUEST' => $_REQUEST,
            'GLOBALS' => $GLOBALS,
            'SESSION' => $_SESSION,
            'SERVER' => $_SERVER,
        );
        
        file_put_contents('C:\xampp\htdocs\booksmartnetwork.com\Services\test.txt', print_r($array,1));
    }
}

