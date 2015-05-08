<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_ExceptionHandler Extends Exception
{
    public static function printException(Exception $e)
    {
        print '<pre>';
        print 'Uncaught ' . get_class($e) . ', code: ' . $e->getCode() . "<br />Message: " . htmlentities($e->getMessage()) . PHP_EOL;
        print htmlentities(print_r($e, 1));
        print '</pre>';
    }
    
    
    public static function handleException(Exception $e)
    {
        self::printException($e);
    }
}

