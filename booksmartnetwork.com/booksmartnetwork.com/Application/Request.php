<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_Request
{
    private static $_superGlobalLookup = array(
        'g' => '_GET',
        'p' => '_POST',
        'r' => '_REQUEST',
        'c' => '_COOKIE',
        's' => '_SERVER',
        'e' => '_ENV',
        'f' => '_FILES'
    );
    
    private static $_superGlobalSize = array();
    
    protected static $_GPC = array();
    protected static $_GPCExists = array();
    
    
    public function __construct()
    {
        static $_init = false;
        
        if (!$_init)
        {
            if (!is_array($GLOBALS))
            {
                throw new Exception('Invalid URL');
            }
            
            
            // Overwrite GET[x] and REQUEST[x] with POST[x] if it exists (overrides server's GPC order preference)
            if ($_SERVER['REQUEST_METHOD'] == 'POST')
            {
                foreach (array_keys($_POST) AS $key)
                {
                    if (isset($_GET[$key])) $_GET[$key] = $_REQUEST[$key] = $_POST[$key];
                }
            }
            
            
            // Reverse the effects of magic quotes if necessary
            if (get_magic_quotes_gpc())
            {
                $this->_stripSlashesDeep($_REQUEST);
                $this->_stripSlashesDeep($_GET);
                $this->_stripSlashesDeep($_POST);
                $this->_stripSlashesDeep($_COOKIE);
                
                if (is_array($_FILES))
                {
                    foreach ($_FILES AS $key => $value)
                    {
                        $_FILES[$key]['tmp_name'] = str_replace('\\', '\\\\', $value['tmp_name']);
                    }
                    
                    $this->_stripSlashesDeep($_FILES);
                }
            }
            
            
            @set_magic_quotes_runtime(0);
            @ini_set('magic_quotes_sybase', 0);
            
            
            // Reverse the effects of register_globals if necessary
            if (ini_get('register_globals') OR !ini_get('gpc_order'))
            {
                foreach (self::$_superGlobalLookup AS $global)
                {
                    self::$_superGlobalSize[$global] = sizeof(@$GLOBALS[$global]);
                    
                    foreach (array_keys((array)@$GLOBALS[$global]) AS $key)
                    {
                        if (!in_array($key, self::$_superGlobalLookup)) unset($GLOBALS[$key]);
                    }
                }
            }
            else
            {
                foreach (self::$_superGlobalLookup AS $global)
                {
                    self::$_superGlobalSize[$global] = sizeof($GLOBALS[$global]);
                }
            }
            
            
            // Deal with cookies that may conflict with _GET and _POST data, and create our own _REQUEST with no _COOKIE input
            foreach (array_keys($_COOKIE) AS $key)
            {
                unset($_REQUEST[$key]);
                
                if (isset($_POST[$key]))
                {
                    $_REQUEST[$key] =& $_POST[$key];
                }
                else if (isset($_GET[$key]))
                {
                    $_REQUEST[$key] =& $_GET[$key];
                }
            }
            
            
            $_init = true;
        }
    }
    
    
    public function get($key)
    {
        return self::$_GPC[$key];
    }
    
    
    public function GPCExists($key)
    {
        return (bool)self::$_GPCExists[$key];
    }
    
    
    public function clean($param1, $param2 = NULL, $param3 = NULL)
    {
        if (is_int($param2))
        {
            // Clean
            return $this->_prepare($param1, $param2);
        }
        else if (is_array($param1) AND is_array($param2))
        {
            // Clean Array
            $array = array();
            
            foreach ($param2 AS $key => $type)
            {
                $key = $this->_doClean($key, TYPE::_STR);
                $type = $this->_doClean($type, TYPE::_UINT);
                
                $array[$key] = $this->_prepare($param1[$key], $type);
            }
            
            return $array;
        }
        else if (is_string($param1) AND in_array($param1, array_keys(self::$_superGlobalLookup)) AND is_array($param2))
        {
            // Clean GPC Array
            $_sg = $GLOBALS[self::$_superGlobalLookup[$param1]];
            
            foreach ($param2 AS $key => $type)
            {
                if (!isset(self::$_GPC[$key]))
                {
                    $key = $this->_doClean($key, TYPE::_STR);
                    $type = $this->_doClean($type, TYPE::_UINT);
                    
                    self::$_GPCExists[$key] = isset($_sg[$key]);
                    
                    self::$_GPC[$key] = $this->_prepare($_sg[$key], $type);
                }
            }
        }
        else if (is_string($param1) AND in_array($param1, array_keys(self::$_superGlobalLookup)) AND is_string($param2) AND is_int($param3))
        {
            // Clean GPC
            if (!isset(self::$_GPC[$param2]))
            {
                $_sg = $GLOBALS[self::$_superGlobalLookup[$param1]];
                
                self::$_GPCExists[$param2] = isset($_sg[$param2]);
                
                self::$_GPC[$param2] = $this->_prepare($_sg[$param2], $param3);
            }
            
            return self::$_GPC[$param1];
        }
        else
        {
            // No Clean
            return $param1;
        }
    }
    
    
    protected function _prepare($data, $type)
    {
        if ($type < TYPE::_CONVERT_VALUE)
        {
            $data = $this->_doClean($data, $type);
        }
        else if (is_array($data))
        {
            if ($type >= TYPE::_CONVERT_KEYS)
            {
                $data = array_keys($data);
                $type -= TYPE::_CONVERT_KEYS;
            }
            else
            {
                $type -= TYPE::_CONVERT_VALUE;
            }
            
            foreach (array_keys($data) AS $key)
            {
                $data[$key] = $this->_doClean($data[$key], $type);
            }
        }
        else
        {
            $data = array();
        }
        
        return $data;
    }
    
    
    protected function _doClean($data, $type)
    {
        static $_booltypes = array('1', 'yes', 'y', 'true', 't', 'on', 'enable', 'enabled');
        
        switch ($type)
        {
            case TYPE::_INT:
                $data = intval($data);
                break;
                
            case TYPE::_UINT:
                $data = (($value = intval($data)) < 0) ? 0 : $value;
                break;
                
            case TYPE::_FLOAT:
                $data = floatval($data);
                break;
                
            case TYPE::_UFLOAT:
                $data = (($value = floatval($data)) < 0) ? 0. : $value;
                break;
                
            case TYPE::_STR_TO_UINT:
            case TYPE::_STR_TO_INT:
                $data = preg_replace('/[^0-9-]/', '', (string)$data);
                $data = intval($data);
                if ($type == TYPE::_STR_TO_UINT) $data = ($data < 0) ? 0 : $data;
                break;
                
            case TYPE::_STR_TO_UFLOAT:
            case TYPE::_STR_TO_FLOAT:
                $data = preg_replace('/[^0-9-]/', '', (string)$data);
                $data = floatval($data);
                if ($type == TYPE::_STR_TO_UFLOAT) $data = ($data < 0) ? 0. : $data;
                break;
                
            case TYPE::_BINARY:
                if (version_compare(PHP_VERSION, '5.2.1', '>='))
                {
                    $data = (binary)$data;
                }
                else
                {
                    $data = strval($data);
                }
                break;
                
            case TYPE::_STR:
                $data = trim(strval($data));
                break;
                
            case TYPE::_USTR:
                $data = strtoupper(trim(strval($data)));
                break;
                
            case TYPE::_LSTR:
                $data = strtolower(trim(strval($data)));
                break;
                
            case TYPE::_NOTRIM:
                $data = strval($data);
                break;
                
            case TYPE::_NOHTML:
                $data = htmlentities(trim(strval($data)));
                break;
                
            case TYPE::_BOOL:
                $data = in_array(strtolower($data), $_booltypes) ? true : false;
                break;
                
            case TYPE::_ARRAY:
                $data = (is_array($data)) ? $data : array();
                break;
                
            case TYPE::_FILE:
                if (is_array($data))
                {
                    if (is_array($data['name']))
                    {
                        for ($i = 0; $i < count($data['name']); $i++)
                        {
                            $data['name'][$i] = $this->_doClean($data['name'][$i], TYPE::_STR);
                            $data['type'][$i] = $this->_doClean($data['type'][$i], TYPE::_STR);
                            $data['tmp_name'][$i] = $this->_doClean($data['tmp_name'][$i], TYPE::_STR);
                            $data['error'][$i] = $this->_doClean($data['error'][$i], TYPE::_UINT);
                            $data['size'][$i] = $this->_doClean($data['size'][$i], TYPE::_UINT);
                        }
                    }
                    else
                    {
                        $data['name'] = $this->_doClean($data['name'], TYPE::_STR);
                        $data['type'] = $this->_doClean($data['type'], TYPE::_STR);
                        $data['tmp_name'] = $this->_doClean($data['tmp_name'], TYPE::_STR);
                        $data['error'] = $this->_doClean($data['error'], TYPE::_UINT);
                        $data['size'] = $this->_doClean($data['size'], TYPE::_UINT);
                    }
                }
                else
                {
                    $data = array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => 4,
                        'size' => 0
                    );
                }
                break;
                
            case TYPE::_UNIXTIME:
                if (is_array($data))
                {
                    $data = $this->_prepare($data, TYPE::_ARRAY_INT);
                    
                    if ($data['hour'] OR $data['minute'] OR $data['second'] OR $data['month'] OR $data['day'] OR $data['year'])
                    {
                        $data = mktime((int)$data['hour'], (int)$data['minute'], (int)$data['second'], (int)$data['month'], (int)$data['day'], (int)$data['year']);
                    }
                    else
                    {
                        $data = 0;
                    }
                }
                else if (($time = strtotime($data)) > 0)
                {
                    $data = $time;
                }
                else
                {
                    $data = $this->_doClean($data, TYPE::_UINT);
                }
                break;
                
            case TYPE::_PHONE:
                $data = preg_replace('/[^0-9]/', '', (string)$data);
                $data = substr($data, -10, 3) . '-' . $data = substr($data, -7, 3) . '-' . $data = substr($data, -4, 4);
                break;
                
            case TYPE::_ZIP:
                $data = preg_replace('/[^0-9]/', '', (string)$data);
                if (strlen($data) > 5) $data = substr($data, 0, 5) . '-' . sprintf('%04d', substr($data, 5, 4));
                if (strlen($data) < 5) $data = sprintf('%05d', $data);
                break;
        }
        
        
        switch ($type)
        {
            case TYPE::_STR:
            case TYPE::_NOTRIM:
            case TYPE::_NOHTML:
                $data = str_replace(chr(0), '', (string)$data);
                if (mb_detect_encoding($data) != 'UTF-8' OR !mb_check_encoding($data, 'UTF-8')) $data = utf8_encode($data);
                break;
        }
        
        return $data;
    }
    
    
    protected function _stripSlashesDeep(array &$array)
    {
        if (is_array($array))
        {
            foreach ($array AS $key => $value)
            {
                if (is_string($value))
                {
                    $array[$key] = stripslashes($value);
                }
                else if (is_array($value))
                {
                    $this->_stripSlashesDeep($array[$key], $depth + 1);
                }
            }
        }
    }
}


// temporary
define('INT', TYPE::_INT);
define('STR', TYPE::_STR);
define('STR_NOHTML', TYPE::_NOHTML);
define('FILE', TYPE::_FILE);

