<?php
  
  if (ip2long($_SERVER['SERVER_ADDR']) !== ip2long($_SERVER['REMOTE_ADDR'])) die(header('HTTP/1.1 403 Forbidden'));
  
  /*** error reporting on ***/
  error_reporting(E_ALL ^ E_NOTICE);
  
  
  /*** include the App.php file ***/
  include 'Application/App.php';
  
  
  App::run(false);
  
  #include 'Services/Test.php';
  
  App::getSingleton('Services_Test');
  

