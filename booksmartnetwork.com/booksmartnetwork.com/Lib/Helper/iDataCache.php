<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Interface Lib_Helper_iDataCache
{
    public function get($key);
    
    public function set($key, $data);
    
    public function delete($key);
    
    public function exists($key);
    
    public function clearCache();
}

