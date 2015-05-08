<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_Helper_ExceptionCodes
{
    /*  Exception Codes  */
    const APP = 100;
    const APP_NOFILE = 101;
    const APP_NOCLASS = 102;
    const APP_NOINTERFACE = 103;
    
    
    private static $messages = array(
        // [Informational 1xx]
        100 => '',
        101 => 'File does not exists',
        102 => 'Class does not exists',
        103 => 'Interface does not exists',
    );
    
    
    public static function getMessageForCode($code)
    {
        return self::$messages[$code];
    }
}

