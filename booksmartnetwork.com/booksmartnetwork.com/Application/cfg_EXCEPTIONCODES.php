<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class EXCEPTIONCODES
{
    /*  Exception Codes  */
    const OTHER = 100;
    const NO_FILE = 101;
    const NO_CLASS = 102;
    const NO_INTERFACE = 103;
    const NOT_AUTHORIZED = 200;
    
    
    private static $messages = array(
        // [Informational 1xx]
        100 => '',
        101 => 'File does not exists',
        102 => 'Class does not exists',
        103 => 'Interface does not exists',
        200 => 'User not authorized',
    );
    
    
    public static function getMessageForCode($code)
    {
        return self::$messages[$code];
    }
}

