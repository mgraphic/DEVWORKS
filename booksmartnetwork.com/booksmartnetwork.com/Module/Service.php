<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_Service Extends Application_View
{
    protected $_service = array(
        'url' => '',
        'method' => 'post',
        'params' => array(),
        'as_user' => true,
    );
    
    
    public function invoke()
    {
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        if (method_exists("Module_{$_view}_{$_path}_Service", 'service' . $this->_currentModule['page']))
        {
            call_user_func(array($this, 'service' . $this->_currentModule['page']));
            
            $this->_run();
        }
        
        return $this;
    }
    
    
    protected function _run()
    {
        if (empty($this->_service['url'])) return false;
        
        $_params = (array)$this->_service['params'];
        
        $_parts = @parse_url($this->_service['url']);
        
        $_method = (strtolower($this->_service['method']) == 'post') ? 'POST' : 'GET';
        
        if (!$_parts) return false;
        
        $_get = $_headers = array();
        
        if ($_parts['query']) parse_str($_parts['query'], $_get);
        
        if ($_method == 'GET') $_get = array_merge($_get, $_params);
        
        if ($this->_service['as_user'])
        {
            $_get = array_merge($_get, array($this->_session->name() => $this->_session->id()));
        }
        
        $_headers[] = $_method . ' ' . $_parts['path'] . ((count($_get) > 0) ? '?' . http_build_query($_get, NULL, '&') : '') . ' HTTP/1.1';
        
        $_headers[] = 'Host: ' . $_parts['host'];
        
        if ($_method == 'POST')
        {
            $_headers[] = 'Content-Type: application/x-www-form-urlencoded';
            
            if ($_params = http_build_query($_params, NULL, '&'))
            {
                $_headers[] = 'Content-Length: ' . strlen($_params);
            }
        }
        else
        {
            $_headers[] = 'Content-Type: text/text';
        }
        
        if ($this->_service['as_user'] AND count($_COOKIE) > 0) $_headers[] = 'Cookie: ' . http_build_query($_COOKIE, NULL, ';');
        
        $_headers[] = 'Connection: Close';
        
        $_headers[] = '';
        
        if (is_string($_params)) $_headers[] = $_params;
        
        $_port = (strtolower($_parts['scheme']) == 'https') ? 443 : 80;
        
        if (isset($_parts['port']) AND $_parts['port'] > 0) $_port = (int)$_parts['port'];
        
        $fp = fsockopen($_parts['host'], $_port);
        
        fwrite($fp, implode("\r\n", $_headers));
        
        fclose($fp);
        
        return true;
    }
}

