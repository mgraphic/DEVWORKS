<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_LinkBuilder Extends Application_Extender
{
    public function __construct()
    {
        parent::__construct();
    }
    
    
    public function url($page, $params = '', $ssl = 'NONSSL', $addSessionId = true, $absolute = true)
    {
        $_url = '';
        
        $_ssl = strtoupper($ssl);
        
        if (in_array($_ssl, array('AUTO', 'KEEP', 'MATCH'))) $_ssl = $this->getProtocal();
        
        $_url .= ($_ssl == 'SSL' AND App::getConfig('ssl_enable')) ? App::getConfig('ssl_server') : App::getConfig('server');
        
        $_url = ($absolute) ? $_url . PR . '' : PR . '';
        
        $_url .= $this->buildPage($page);
        
        $_parameters = $this->_parseParams($params);
        
        if ($addSessionId AND defined('SID') AND SID != '') $_parameters[$this->_session->name()] = $this->_session->id();
        
        $_parameters = (count($_parameters) > 0) ? '?' . http_build_query($_parameters) : '';
        
        while (strstr($_parameters, '&&')) $_parameters = str_replace('&&', '&', $_parameters);
        
        return $_url . $_parameters;
    }
    
    
    public function buildPage($page)
    {
        $_pageArray = (is_array($page)) ? $this->pageArray(implode('/', $page)) : $this->pageArray($page);
        
#        $_page = array();
        
#        if (!empty($_pageArray['view'])) $_page['view'] = $_pageArray['view'];
#        if (!empty($_pageArray['path'])) $_page['path'] = $_pageArray['path'];
#        if (!empty($_pageArray['page'])) $_page['page'] = $_pageArray['page'];
        
        return $this->pageString($_pageArray);
    }
    
    
    public function relative(Array $pageArray, $params = '', $ssl = 'NONSSL', $addSessionId = true)
    {
        return $this->url($pageArray, $params, $ssl, $addSessionId, false);
    }
    
    
    public function getParams(Array $exclude = array(), $asString = false)
    {
        $_exclude[] = $this->_session->name();
        
        $_params = array();
        
        foreach ($_GET AS $key => $value)
        {
            if (!empty($key) AND !in_array($key, $_exclude)) $_params[$key] = $value;
        }
        
        if (count($_params) < 1) return ($asString) ? '' : array();
        
        return ($asString) ? http_build_query($_params) . '&' : $_params;
    }
    
    
    public function encode($value)
    {
        #if (!is_scalar($value)) return urlencode(serialize($value));
        #if (is_array($value)) return http_build_query($value);
        if (is_array($value)) $value = http_build_query($value);
        
        $_string = $this->clean($value, TYPE::_STR);
        
        return urlencode($_string);
    }
    
    
    protected function _parseParams($params)
    {
        $_parameters = array();
        
        if (is_string($params))
        {
            $params = $this->clean($params, TYPE::_STR);
            
            if (strpos($params, '=')) parse_str($params, $_parameters);
        }
        else if (!is_array($params))
        {
            return array();
        }
        
        if (isset($_parameters[$this->_session->name()])) unset($_parameters[$this->_session->name()]);
        
        $array = $_parameters;
        
        $_parameters = array();
        
        foreach ($array AS $key => $value) if (!empty($key) AND !is_integer($key)) $_parameters[$key] = $value;
        
        return  array_unique($_parameters);
    }
}

