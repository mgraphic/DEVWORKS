<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_DataCacheDBM Implements Lib_Helper_iDataCache
{
    protected $_dbm;
    protected static $_now;
    
    
    public function __construct($cacheFile)
    {
        if (!isset(self::$_now)) self::$_now = time();
        
        $_cacheDir = App::getConfig('Lib/DataCacheDBM/cache_dir');
        
        $this->_dbm = dba_popen($_cacheDir . DS . $cacheFile, 'cl', 'db4');
        
        if (!$this->_dbm)
        {
            throw new Exception("$cacheFile: Unable to open DBM cache file");
        }
    }
    
    
    public function get($key, $expiration = 0)
    {
        $_data = dba_fetch($key, $this->_dbm);
        
        if ($_data !== false)
        {
            $_data = unserialize($_data);
            
            if ($_data['timestamp'] + $expiration > self::$_now)
            {
                return $_data['cache'];
            }
            else
            {
                $this->delete($key);
            }
        }
        
        return NULL;
    }
    
    
    public function set($key, $data, $expiration = 0)
    {
        $_data = array('timestamp' => self::$_now, 'cache' => $data);
        
        if (!dba_replace($key, serialize($_data), $this->_dbm))
        {
            #throw new Exception("$key: Unable to store DBM cache");
        }
    }
    
    
    public function delete($key)
    {
        dba_delete($key, $this->_dbm);
    }
    
    
    public function exists($key)
    {
        return dba_exists($key, $this->_dbm);
    }
    
    
    public function clearCache()
    {
        if ($key = dba_firstkey($this->_dbm))
        {
            $this->delete($key);
            
            while ($key = dba_nextkey($this->_dbm)) $this->delete($key);
        }
    }
    
    
    public function __destruct()
    {
        #dba_close($this->_dbm);
    }
}

