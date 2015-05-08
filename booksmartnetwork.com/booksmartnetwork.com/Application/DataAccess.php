<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_DataAccess
{
    private static $_pdo = NULL;
    private static $_cache = array();
    private static $_cacheEnabled = false;
    private static $_fetchMode;
    
    private $_moduleName;
    private $_pdoStatement;
    private $_currentResult;
    private $_parameters;
    private $_expiration;
    
    
    public function __construct($moduleName = '')
    {
        $this->_parameters = array();
        
        if (is_null(self::$_pdo))
        {
            self::$_cacheEnabled = App::getConfig('Application/DataAccess/cache_enabled');
            
            self::$_fetchMode = App::getConfig('Application/DataAccess/fetch_mode');
            
            $_dsn = array();
            $_config = App::getConfig('Application/DataAccess/dsn');
            foreach ((array)$_config AS $value) $_dsn[$value['name']] = $value['value'];
            
            $_options = array();
            $_config = App::getConfig('Application/DataAccess/options');
            foreach ((array)$_config AS $value) $_options[$value['name']] = $value['value'];
            
            $_attributes = array();
            $_config = App::getConfig('Application/DataAccess/attributes');
            foreach ((array)$_config AS $value) $_attributes[$value['name']] = $value['value'];
            
            $_driver = App::getConfig('Application/DataAccess/driver');
            $_user = App::getConfig('Application/DataAccess/user');
            $_pass = App::getConfig('Application/DataAccess/pass');
            
            $_dsnParams = array();
            
            foreach ($_dsn AS $key => $value)
            {
                if ($key) $_dsnParams[] = strtolower($key) . '=' . $value;
            }
            
            $_dsnParams = $_driver . ':' . implode(';', $_dsnParams);
            
            $_pdoOptions = array();
            
            foreach ($_options AS $key => $value)
            {
                if (defined("PDO::$key"))
                {
                    $_pdoOptions[constant("PDO::$key")] = $value;
                }
            }
            
            try
            {
                self::$_pdo = new PDO($_dsnParams, $_user, $_pass, $_pdoOptions);
            }
            catch (Exception $e)
            {
                throw new Exception('Connection failed: ' . $e->getMessage());
            }
            
            foreach ($_attributes AS $key => $value)
            {
                if (is_string($value) AND !empty($value) AND defined("PDO::$value")) $value = constant("PDO::$value");
                
                if (defined("PDO::$key"))
                {
                    self::$_pdo->setAttribute(constant("PDO::$key"), $value);
                }
            }
        }
        
        
        $this->_moduleName = ($moduleName) ? $moduleName : get_class($this);
        
        $this->_expiration = ($expire = App::getConfig(0) > 0) ? $expire : 3600;
        
        if (self::$_cacheEnabled AND !isset(self::$_cache[$this->_moduleName]))
        {
            self::$_cache[$this->_moduleName] = App::getInstance('Lib_DataCacheDBM', array('DataAccess.' . $this->_moduleName . '.dbm'));
        }
    }
    
    
    public function prepare($statement)
    {
        $this->_pdoStatement = NULL;
        
        try
        {
            $this->_pdoStatement = self::$_pdo->prepare($statement);
        }
        catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
        
        return $this;
    }
    
    
    public function bind($parameter, $value, $dataType = NULL)
    {
        if (!isset($dataType))
        {
            switch (true)
            {
                case is_bool($value): $dataType = PDO::PARAM_BOOL; break;
                case is_null($value): $dataType = PDO::PARAM_NULL; break;
                case is_int($value): $dataType = PDO::PARAM_INT; break;
                default: $dataType = PDO::PARAM_STR;
            }
        }
        
        if ($this->_pdoStatement instanceof PDOStatement)
        {
            try
            {
                $this->_pdoStatement->bindValue($parameter, $value, $dataType);
                $this->_parameters[$parameter] = (string)$value;
            }
            catch (Exception $e)
            {
                throw new Exception($e->getMessage());
            }
        }
        else
        {
            throw new Exception('Unable to bind parameter on a non prepared object');
        }
        
        return $this;
    }
    
    
    /*
    * Return and iterate an executed statement one row at a time
    * 
    * Fetch next row of an executed statement:
    * mixed fetch ( void )
    * 
    */
    public function fetch()
    {
        if (isset($this->_currentResult) AND is_array($this->_currentResult))
        {
            if (count($this->_currentResult) > 0)
            {
                return array_shift($this->_currentResult);
            }
        }
        
        return false;
    }
    
    
    /*
    * Returns full result array on a select query
    * 
    * Execute a new statement:
    * mixed execute ( string $statement [, array $parameters = null [, int $expiration = null ]] )
    * 
    * Execute a prepared statement:
    * mixed execute ( [ array $parameters = null [, int $expiration = null ]] )
    * 
    */
    public function execute()
    {
        $_arguments = func_get_args();
        
        if (isset($_arguments[0]) AND is_string($_arguments[0]))
        {
            $this->prepare($_arguments[0]);
            
            $_parameters = (isset($_arguments[1])) ? (array)$_arguments[1] : NULL;
            
            $_expiration = (isset($_arguments[2])) ? (int)$_arguments[2] : NULL;
        }
        else
        {
            $_parameters = (isset($_arguments[0])) ? (array)$_arguments[0] : NULL;
            
            $_expiration = (isset($_arguments[1])) ? (int)$_arguments[1] : NULL;
        }
        
        if (!($this->_pdoStatement instanceof PDOStatement))
        {
            throw new Exception('Unable to execute on a non prepared object');
        }
        
        $_statement = strtolower(ltrim($this->_pdoStatement->queryString));
        
        if (substr($_statement, 0, 6) == 'select' OR substr($_statement, 0, 4) == 'show')
        {
            return $this->_read($_parameters, $_expiration);
        }
        
        return $this->_execute($_parameters);
    }
    
    
    private function _read(Array $parameters = NULL, $expiration = NULL)
    {
        $_cacheEnabled = self::$_cacheEnabled;
        
        $_expiration = (isset($expiration)) ? (int)$expiration : $this->_expiration;
        
        $_queryString = preg_replace('/[[:space:][:cntrl:]]/s', '', strtolower(trim($this->_pdoStatement->queryString)));
        
        $_paramString = serialize(isset($parameters) ? array_map('strval', (array)$parameters) : $this->_parameters);
        
        $_cacheKey = sha1($_queryString . $_paramString);
        
        if (self::$_cacheEnabled AND $_result = self::$_cache[$this->_moduleName]->get($_cacheKey, $_expiration) !== NULL)
        {
            $this->_pdoStatement = NULL;
            $this->_parameters = array();
            $this->_currentResult = (array)$_result;
            
            return $this->_currentResult;
        }
        
        if (isset($parameters))
        {
            $this->_pdoStatement->execute($parameters);
        }
        else
        {
            $this->_pdoStatement->execute();
        }
        
        $_result = $this->_pdoStatement->fetchAll(self::$_fetchMode);
        
        if (self::$_cacheEnabled) self::$_cache[$this->_moduleName]->set($_cacheKey, $_result, $_expiration);
        
        $this->_pdoStatement = NULL;
        $this->_parameters = array();
        $this->_currentResult = (array)$_result;
        
        return $this->_currentResult;
    }
    
    
    private function _execute(Array $parameters = NULL)
    {
        if (isset($parameters))
        {
            $_result = $this->_pdoStatement->execute($parameters);
        }
        else
        {
            $_result = $this->_pdoStatement->execute();
        }
        
        $this->_pdoStatement = NULL;
        $this->_parameters = array();
        $this->_currentResult = array();
        
        return $_result;
    }
    
    
    public function sql($statement)
    {
        return self::$_pdo->exec($statement);
    }
    
    
    public function quoteString($string)
    {
        return self::$_pdo->quote($string);
    }
    
    
    public function getInsertId($name = NULL)
    {
        return self::$_pdo->lastInsertId($name);
    }
    
    
    /*
    * Strip SQL wildcards '%'
    * 
    */
    public function stripWildcards($string)
    {
        return str_replace(array('%', '_'), '', $string);
    }
    
    
    public function escapeWildcards($string, $escapeChar = '!')
    {
        return str_replace(array('%', '_'), array($escapeChar . '%', $escapeChar . '_'));
    }
    
    
    public function clearCache()
    {
        if ($this->_cacheEnabled) self::$_cache[$this->_moduleName]->clearCache();
    }
    
    
    public function getTables()
    {
        $_result = $this->execute("SHOW TABLES");
        
        $_array = array();
        
        foreach ($_result AS $_table)
        {
            list($key, $value) = each($_table);
            
            $_array[] = $value;
        }
        
        return $_array;
    }
    
    
    public function tableExists($table)
    {
        $_result = $this->execute("SHOW TABLES LIKE ?", array($table));
        
        if (count($_result) > 0)
        {
            foreach ($_result AS $_table)
            {
                list($key, $value) = each($_table);
                
                if ($table == $value) return true;
            }
        }
        
        return false;
    }
}

