<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
/** Singleton class **/
Class Application_SessionHandler
{
    private static $_thisInstance;
    
    protected static $_opened = false;
    protected static $_closed = false;
    
    protected $_db;
    protected $_expiry;
    protected $_sessionTable;
    
    
    private function __construct()
    {
        $this->_expiry = time() + App::getConfig('Application/SessionHandler/expire');
        
        $this->_sessionTable = App::getConfig('Application/SessionHandler/sql_tables/sessions/name');
        
        $this->_db = App::getInstance('Application_DataAccess', array(__CLASS__));
        
        $this->_setSaveHandler();
        
        $this->_savePath();
        
        $this->_setCookieParams();
        
        $this->name(App::getConfig('Application/SessionHandler/session_name'));
        
        $this->_start();
    }
    
    
    private function __clone() { }
    
    
    public static function singleton()
    {
        if (!isset(self::$_thisInstance))
        {
            $_class = __CLASS__;
            self::$_thisInstance = new $_class;
        }
        
        return self::$_thisInstance;
    }
    
    
    public function get($key)
    {
        if (isset($_SESSION[$key])) return $_SESSION[$key];
        
        return NULL;
    }
    
    
    public function set($key, $value = NULL)
    {
        $_SESSION[$key] = $value;
    }
    
    
    public function delete($key)
    {
        unset($_SESSION[$key]);
    }
    
    
    public function id($id = NULL)
    {
        if ($id)
        {
            return session_id($id);
        }
        
        return session_id();
    }
    
    
    public function name($name = NULL)
    {
        if ($name)
        {
            return session_name($name);
        }
        
        return session_name();
    }
    
    
    public function recreate()
    {
        $_backup = $_SESSION;
        
        $_SESSION = array();
        
        if (ini_get('session.use_cookies'))
        {
            $_params = session_get_cookie_params();
            
            setcookie($this->name(), '', time() - 42000, $_params['path'], $_params['domain'], $_params['secure'], $_params['httponly']);
        }
        
        $this->_destroy();
        
        self::$_opened = false;
        
        self::$_closed = false;
        
        $this->_setSaveHandler();
        
        $this->_start();
        
        $_SESSION = $_backup;
        
        unset($_backup);
    }
    
    
    protected function _open($path, $name)
    {
        if (!self::$_opened)
        {
            // Do stuff
            self::$_opened = true;
            
            return true;
        }
        
        return false;
    }
    
    
    protected function _start()
    {
        ini_set('session.gc_maxlifetime', App::getConfig('Application/SessionHandler/gc_maxlifetime'));
        ini_set('session.gc_probability', App::getConfig('Application/SessionHandler/gc_probability'));
        
        return session_start();
    }
    
    
    protected function _read($key)
    {
        $this->_db->execute("
            SELECT value
            FROM {$this->_sessionTable}
            WHERE sesskey = ?
              AND expiry > ?
        ", array(
            $key,
            time()
        ), 0);
        
        $_result = $this->_db->fetch();
        
        if (isset($_result['value'])) return $_result['value'];
        
        return '';
    }
    
    
    public function _write($key, $value)
    {
        $_result = $this->_db->execute("
            SELECT COUNT(*) AS total
            FROM {$this->_sessionTable}
            WHERE sesskey = ?
        ", array($key), 0);
        
        if ($_result[0]['total'] > 0)
        {
            $this->_db->execute("
                UPDATE {$this->_sessionTable}
                SET expiry = ?, value = ?
                WHERE sesskey = ?
            ", array(
                $this->_expiry,
                $value,
                $key
            ));
        }
        else
        {
            $this->_db->execute("
                INSERT INTO {$this->_sessionTable} (sesskey, expiry, value)
                VALUES (?, ?, ?)
            ", array(
                $key,
                $this->_expiry,
                $value
            ));
        }
        
        return true;
    }
    
    
    public function _close()
    {
        if (!self::$_closed)
        {
            // Do stuff
            self::$_closed = true;
            
            return true;
        }
        
        return false;
    }
    
    
    protected function _destroy($key)
    {
        $this->_db->execute("
            DELETE FROM {$this->_sessionTable}
            WHERE sesskey = ?
        ", array($key));
        
        $this->_db->clearCache();
        
        return true;
    }
    
    
    protected function _gc($lifetime)
    {
        $this->_db->execute("
            DELETE FROM {$this->_sessionTable}
            WHERE expiry < ?
        ", array(time()));
        
        return true;
    }
    
    
    protected function _setCookieParams()
    {
        $_lifetime = App::getConfig('Application/SessionHandler/cookie_parameters/lifetime');
        $_path = App::getConfig('Application/SessionHandler/cookie_parameters/path');
        $_domain = App::getConfig('Application/SessionHandler/cookie_parameters/domain');
        $_secure = App::getConfig('Application/SessionHandler/cookie_parameters/secure');
        
        return session_set_cookie_params($_lifetime, $_path, $_domain, $_secure);
    }
    
    
    protected function _savePath()
    {
        $_path = App::getConfig('Application/SessionHandler/cookie_parameters/save_path');
        
        return session_save_path($_path);
    }
    
    
    protected function _setSaveHandler()
    {
        $_open = array($this, '_open');
        $_close = array($this, '_close');
        $_read = array($this, '_read');
        $_write = array($this, '_write');
        $_destroy = array($this, '_destroy');
        $_gc = array($this, '_gc');
        
        return session_set_save_handler($_open, $_close, $_read, $_write, $_destroy, $_gc);
    }
    
    
    public function __destruct()
    {
        $this->_close();
    }
}

