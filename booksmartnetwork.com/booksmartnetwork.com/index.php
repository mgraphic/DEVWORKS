<?php
  
  /*** error reporting on ***/
  error_reporting(E_ALL ^ E_NOTICE);
  
  
  /*** include the App.php file ***/
  include 'Application/App.php';
  
  
  
  App::run();
  

