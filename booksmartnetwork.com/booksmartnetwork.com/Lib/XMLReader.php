<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_XMLReader
{
    protected $_parsedXml;
    protected $_rootName;
    
    
    public function __construct(Lib_XMLParser $xml)
    {
        $this->_parsedXml = $xml->getArray();
        
        reset($this->_parsedXml);
        
        if (count($this->_parsedXml) > 1 OR isset($this->_parsedXml[0]['string']))
        {
            $this->_rootName = '';
        }
        else
        {
            $this->_rootName = $this->_parsedXml[0]['name'];
            
            $this->_parsedXml = $this->_parsedXml[0]['value'];
        }
    }
    
    
    protected function _path($xmlPath)
    {
        $_xml = str_replace("\\", '/', $xmlPath);
        
        if (strlen($_xml) > 1 AND substr($_xml, -1) == '/') $_xml = substr($_xml, 0, -1);
        
        $_xml = explode('/', $_xml);
        
        return $_xml;
    }
    
    
    protected function _find(Array $path, $find, Array &$array, $index = 0)
    {
        if ($find == 'value' OR $find == 'parameters')
        {
            $_output = array();
        }
        else if ($find == 'isset')
        {
            $_output = false;
        }
        else if ($find == 'string')
        {
            $_output = '';
        }
        else
        {
            return NULL;
        }
        
        if (!is_array($array)) return $_output;
        
        $_end = (count($path) === 0 OR count($path) - 1 == $index) ? true : false;
        
        for ($i = 0; $i < count($array); $i++)
        {
            switch ($find)
            {
                case 'value':
                    if (!isset($array[$i]['name']))
                    {
                        break;
                    }
                    if ($_end AND $array[$i]['name'] == $path[$index])
                    {
                        $_output[] = $array[$i]['value'];
                    }
                    else if (!$_end AND $array[$i]['name'] == $path[$index])
                    {
                        $_output = $this->_find($path, $find, &$array[$i]['value'], ++$index);
                    }
                    break;
                    
                case 'isset':
                    if (!isset($array[$i]['name']))
                    {
                        break;
                    }
                    if ($_end AND $array[$i]['name'] == $path[$index])
                    {
                        return true;
                    }
                    else if (!$_end AND $array[$i]['name'] == $path[$index])
                    {
                        return $this->_find($path, $find, &$array[$i]['value'], ++$index);
                    }
                    break;
                    
                case 'parameters':
                    if (!isset($array[$i]['name']))
                    {
                        break;
                    }
                    if ($_end AND $array[$i]['name'] == $path[$index] AND $array[$i]['hasAttributes'])
                    {
                        $_output[] = (array)$array[$i]['attributes'];
                    }
                    else if (!$_end AND $array[$i]['name'] == $path[$index])
                    {
                        $_output = $this->_find($path, $find, &$array[$i]['value'], ++$index);
                    }
                    break;
                    
                case 'string':
                    if (!isset($array[$i]['name']))
                    {
                        break;
                    }
                    if ($_end AND $array[$i]['name'] == $path[$index] AND $array[$i]['hasValue'] AND isset($array[$i]['string']))
                    {
                        $_output = $array[$i]['string'];
                    }
                    else if (!$_end AND $array[$i]['name'] == $path[$index])
                    {
                        $_output = $this->_find($path, $find, &$array[$i]['value'], ++$index);
                    }
                    break;
            }
        }
        
        return $_output;
    }
    
    
    public function get($xmlPath)
    {
        $_xml = $this->_path($xmlPath);
        
        $_result = $this->_find($_xml, 'value', &$this->_parsedXml);
        
        if (count($_result) <= 0)
        {
            return NULL;
        }
        else if (count($_result) > 1)
        {
            return $_result;
        }
        
        return $_result[0];
    }
    
    
    public function exists($xmlPath)
    {
        $_xml = $this->_path($xmlPath);
        
        $_result = $this->_find($_xml, 'isset', &$this->_parsedXml);
        
        return (bool)$_result;
    }
    
    
    public function getParameters($xmlPath)
    {
        $_xml = $this->_path($xmlPath);
        
        $_result = $this->_find($_xml, 'parameters', &$this->_parsedXml);
        
        if (count($_result) <= 0)
        {
            return array();
        }
        else if (count($_result) > 1)
        {
            return $_result;
        }
        
        return $_result[0];
    }
    
    
    public function getString($xmlPath)
    {
        $_xml = $this->_path($xmlPath);
        
        $_result = $this->_find($_xml, 'string', &$this->_parsedXml);
        
        return (string)$_result;
    }
    
    
    public function getRootName()
    {
        return $this->_rootName;
    }
}

