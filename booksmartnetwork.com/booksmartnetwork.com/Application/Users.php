<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_Users Extends Application_Extender
{
    /* Handler properties */
    protected $_db;
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_db = App::getInstance('Application_DataAccess', array(__CLASS__));
        
        $this->userstableName = App::getConfig('Application/User/sql_tables/userstable/name');
        
        $this->userstableCache = App::getConfig('Application/User/sql_tables/userstable/cache');
        
        $this->rolestableName = App::getConfig('Application/User/sql_tables/rolestable/name');
        
        $this->rolestableCache = App::getConfig('Application/User/sql_tables/rolestable/cache');
    }
    
    
    protected function _getRoleId($roleName)
    {
        $roleId = 0;
        
        $_result = $this->_db->execute("
            SELECT id
            FROM {$this->rolestableName}
            WHERE roles =?
        ", array($roleName), $this->rolestableCache);
        
        if (isset($_result[0]['id'])) $roleId = (int)$_result[0]['id'];
        
        return $roleId;
    }
    
    
    protected function _validatePassword($plain, $encrypted)
    {
        if (!empty($plain) AND !empty($encrypted))
        {
            $_stack = explode(':', $encrypted);
            
            if (sizeof($_stack) != 2) return false;
            
            if (sha1($_stack[1] . $plain) == $_stack[0]) return true;
        }
        
        return false;
    }
    
    
    protected function _encryptPassword($plain)
    {
        $_password = '';
        
        for ($i = 0; $i < 10; $i++) $_password .= rand();
        
        $_salt = substr(sha1($_password), 0, 5);
        
        $_password = sha1($_salt . $plain) . ':' . $_salt;
        
        return $_password;
    }
    
    
    public function hasRole($mixed)
    {
        $_userRoles = (array)$this->getInfo('roles');
        
        if (is_array($mixed))
        {
            foreach ($mixed AS $role)
            {
                $_role = $this->clean($role, TYPE::_STR);
                
                if (in_array($_role, $_userRoles, true)) return true;
            }
        }
        else
        {
            $_role = $this->clean($mixed, TYPE::_STR);
            
            if (in_array($_role, $_userRoles, true)) return true;
        }
        
        if (in_array('SUPERUSER', $_userRoles, true)) return true;
        
        return false;
    }
}

