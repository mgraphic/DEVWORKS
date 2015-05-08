<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_User Extends Application_Users
{
    /* Other properties */
    protected $_userData;
    
    
    public function __construct()
    {
        parent::__construct();
        
        if (!$this->getSession('user'))
        {
            $this->reset();
            
            $this->setSession('user', &$this);
            
            $this->loginCookie();
        }
        
        $this->_logUserActivity();
    }
    
    
    public function reset()
    {
        $_defaultRole = App::getConfig('Application/User/default_role');
        
        $this->_userData = array(
            'id' => 0,
            
            'roles' => array($_defaultRole => $_defaultRole),
            
            'name' => array(),
            
            'info' => array(),
            
            'account' => array(),
        );
    }
    
    
    public function loginEmail($email, $password)
    {
        $_email = $this->clean($email, TYPE::_STR);
        
        $_password = $this->clean($password, TYPE::_STR);
        
        $_result = $this->_db->execute("
            SELECT *
            FROM {$this->userstableName}
            WHERE email =?
              AND enabled = 1
        ", array($_email), $this->userstableCache);
        
        if (count($_result) < 1) return false;
        
        if ($this->_validatePassword($_password, $_result[0]['password']))
        {
            $this->_setUser($_result[0]);
            
            return true;
        }
        
        return false;
    }
    
    
    public function loginCookie()
    {
        $_user = $this->clean('c', 'user', TYPE::_STR);
        
        if (!$this->GPCExists['user']) return false;
        
        $_user = explode('|', $_user);
        
        $_id = $this->clean($_user[0], TYPE::_UINT);
        
        $_password = $this->clean($_user[1], TYPE::_STR);
        
        $_result = $this->_db->execute("
            SELECT *
            FROM {$this->userstableName}
            WHERE id = $_id
              AND cookie_password IS NOT NULL
              AND enabled = 1
        ", $this->userstableCache);
        
        if (count($_result) < 1) return false;
        
        if ($this->_validatePassword($_password, $_result[0]['cookie_password']))
        {
            $this->_setUser($_result[0]);
            
            return true;
        }
        
        return false;
    }
    
    
    protected function _setAuthorization($id)
    {
        $_id = $this->clean($id, TYPE::_UINT);
        
        $_result = $this->_db->execute("
            SELECT users_roles
            FROM {$this->rolestableName}
            WHERE users_id = $_id AND users_roles != ''
        ", $this->rolestableCache);
        
        foreach ((array)$_result AS $role)
        {
            $_role = $this->clean($role['users_roles'], TYPE::_USTR);
            
            $this->setInfo("roles/$_role", $_role);
        }
    }
    
    
    protected function _setUser(Array $data)
    {
        $this->setInfo('id', $this->clean($data['id'], TYPE::_UINT));
        
        if (isset($data['first_name'])) $this->setInfo('name/first', $data['first_name']);
        
        if (isset($data['last_name'])) $this->setInfo('name/last', $data['last_name']);
        
        if (isset($data['first_name']) AND isset($data['last_name'])) $this->setInfo('name/full', $data['first_name'] . ' ' . $data['last_name']);
        
        if (isset($data['email'])) $this->setInfo('info/email', $data['email']);
        
        $this->_setAuthorization($data['id']);
        
        $this->setInfo('account/id', $this->clean($data['account_id'], TYPE::_UINT));
    }
    
    
    protected function _logUserActivity()
    {
        $id = $this->getInfo('id');
        
        if (empty($id)) return;
        
        $this->_db->execute("
            UPDATE {$this->userstableName}
            SET activity = NOW()
            WHERE id =?
        ", array(
            $id
        ));
    }
    
    
    public function userData()
    {
        return $this->_userData;
    }
    
    
    public function getInfo($path)
    {
        $_path = $this->clean($path, TYPE::_STR);
        
        if (strlen($_path) < 1) return NULL;
        
        $_parts = $this->_path($_path);
        
        $_data = $this->_userData;
        
        $_exists = true;
        
        for ($i = 0; $i < count($_parts) AND $_exists; $i++)
        {
            $key = $_parts[$i];
            
            if (isset($_data[$key]))
            {
                $_data = $_data[$key];
            }
            else
            {
                $_exists = false;
            }
        }
        
        return ($_exists) ? $_data : NULL;
    }
    
    
    public function setInfo($path, $value)
    {
        $_path = $this->clean($path, TYPE::_STR);
        
        if (strlen($_path) < 1) return;
        
        $_parts = $this->_path($_path);
        
        $_array = $this->_pathToArray($_parts, $value);
        
        $this->_userData = $this->arrayMergeRecurcive((array)$this->_userData, $_array);
    }
    
    
    protected function _pathToArray(Array $path, $endValue = NULL, Array $array = array())
    {
        if (($key = array_shift($path)) !== NULL)
        {
            $value = $this->_pathToArray($path, $endValue, $array);
            
            $array[$key] = (count($path) > 0) ? $value : $endValue;
        }
        
        return $array;
    }
    
    
    protected function _path($path)
    {
        $_path = str_replace("\\", '/', $path);
        
        if (strlen($_path) > 1 AND substr($_path, -1) == '/') $_path = substr($_path, 0, -1);
        
        $_path = explode('/', $_path);
        
        return $_path;
    }
}

