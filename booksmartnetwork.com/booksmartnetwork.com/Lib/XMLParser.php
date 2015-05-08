<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_XMLParser
{
    protected $_xml;
    protected $_parsedXml = array();
    
    
    public function __construct(){}
    
    
    public function parse($load)
    {
        $this->_xml = NULL;
        
        $this->_parsedXml = array();
        
        if (substr(strtolower(ltrim($load)), 0, 5) == '<?xml')
        {
            $this->_loadString($load);
        }
        else
        {
            $this->_loadFile($load);
        }
        
        $array = $this->_parseXML();
        
        $this->_parsedXml = $this->_parseArray($array);
        
        return App::getInstance('Lib_XMLReader', array($this));
    }
    
    
    
    
    public function _loadString($string)
    {
        try
        {
            $this->_xml = new XMLReader;
            $this->_xml->XML($string);
        }
        catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }
    
    
    public function _loadFile($file)
    {
        if (!is_file($file)) throw new Exception('File: ' . $file . ' does not exists');
        if (!is_readable($file)) throw new Exception('File: ' . $file . ' is not readable');
        
        try
        {
            $this->_xml = new XMLReader;
            $this->_xml->open($file);
        }
        catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }
    
    
    public function _parseXML()
    {
        $_tree = array();
        
        while ($this->_xml->read())
        {
            switch ($this->_xml->nodeType)
            {
                case XMLReader::END_ELEMENT:
                    return $_tree;
                    
                case XMLReader::ELEMENT:
                    $_key = $this->_xml->name;
                    
                    $_attributeCount = $this->_xml->attributeCount;
                    
                    $_value = $this->_xml->isEmptyElement ? '' : $this->_parseXML();
                    
                    $_node = array(
                        'attributes' => array(),
                        'attributeCount' => $_attributeCount,
                        'depth' => $this->_xml->depth,
                        'hasAttributes' => $this->_xml->hasAttributes,
                        'isDefault' => $this->_xml->isDefault,
                        'isEmptyElement' => $this->_xml->isEmptyElement,
                        'localName' => $this->_xml->localName,
                        'name' => $_key,
                        'namespaceURI' => $this->_xml->namespaceURI,
                        'prefix' => $this->_xml->prefix,
                        'xmlLang' => $this->_xml->xmlLang,
                        'value' => $_value,
                    );
                    
                    if ($this->_xml->hasAttributes)
                    {
                        while ($this->_xml->moveToNextAttribute())
                        {
                            $_node['attributes'][$this->_xml->name] = $this->_xml->value;
                        }
                    }
                    
                    $_tree[] = $_node;
                    break;
                    
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    $_tree = $this->_xml->value;
            }
        }
        
        return $_tree;
    }
    
    
    protected function _parseArray($array)
    {
        $_parsed = array('hasValue' => false);
        
        $_type = 'string';
        
        $_parameters = array();
        
        while (list($key, $value) = each($array))
        {
            if (is_int($key))
            {
                unset($_parsed['hasValue']);
                
                $_parsed[] = $this->_parseArray((array)$value);
            }
            else
            {
                if ($key == 'name')
                {
                    $_parsed['name'] = (string)$value;
                }
                else if ($key == 'attributes')
                {
                    foreach ((array)$value AS $k => $v)
                    {
                        $value[strtolower(trim($k))] = trim($v);
                    }
                    
                    if (isset($value['type'])) $_type = $value['type'];
                    
                    $_parameters = $value;
                    
                    $_parsed['attributes'] = $value;
                }
                else if ($key == 'value')
                {
                    if (is_array($value) AND count($value) > 0)
                    {
                        $_parsed['value'] = $this->_parseArray($value);
                    }
                    else
                    {
                        if (is_array($value)) $value = '';
                        $_parsed['string'] = $value;
                        $_parsed['hasValue'] = true;
                        $_parsed['value'] = $this->_forceType($_type, $value, $_parameters);
                    }
                }
                else
                {
                    $_parsed[trim($key)] = $value;
                }
            }
        }
        
        if (isset($_parsed['attributes']) AND count($_parsed['attributes']) <= 0) unset($_parsed['attributes']);
        
        return $_parsed;
    }
    
    
    protected function _forceType($type, $value, $parameters)
    {
        static $_booltypes = array('1', 'yes', 'y', 'true', 't', 'on', 'enable', 'enabled');
        
        $value = (string)$value;
        
        switch (strtolower($type))
        {
            case 'null':
                $value = NULL;
                break;
                
            case 'int':
            case 'integer':
                $value = intval($value);
                break;
                
            case 'float':
            case 'double':
            case 'decimal':
                $value = floatval($value);
                break;
                
            case 'bool':
            case 'boolean':
                $value = in_array(strtolower($value), $_booltypes) ? true : false;
                break;
                
            case 'array':
                $_sep = (isset($parameters['split'])) ? $parameters['split'] : ',';
                $value = explode($_sep, $value);
                break;
                
            case 'time':
                $value = (int)strtotime($value, 0);
                break;
                
            case 'date':
                $value = (int)strtotime($value);
                break;
                
            case 'const':
            case 'constant':
                $value = (defined($value)) ? constant($value) : NULL;
                break;
                
            case 'path':
                $value = str_replace("\\", '/', $value);
                $value = str_replace(
                    array('/', '%BASE_PATH%', '%BP%', '%PUBLIC_ROOT%', '%PR%', '%DIRECTORY_SEPARATOR%', '%DS%', '%PATH_SEPARATOR%', '%PS%'),
                    array(DIRECTORY_SEPARATOR, BASE_PATH, BP, PUBLIC_ROOT, PR, DIRECTORY_SEPARATOR, DS, PATH_SEPARATOR, PS), $value
                );
                
                if (strlen($value) > 1 AND substr($value, -1) == DIRECTORY_SEPARATOR) $value = substr($value, 0, -1);
                break;
                
            case 'callback':
                $value = str_replace(array('=>', '->', '>'), '::', $value);
                $value = preg_replace('/[^A-Za-z0-9_:]/', '', $value);
                $value = explode('::', $value);
                if (count($value) === 1) $value = $value[0];
                if (count($value) > 2) $value = array($value[0], $value[1]);
                break;
                
            default:
                $value = strval($value);
                break;
        }
        
        return $value;
    }
    
    
    public function getArray()
    {
        return $this->_parsedXml;
    }
}

