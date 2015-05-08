<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_VarFile Extends Lib_FileHandeling
{
    protected $_fileObject;
    protected $_filenameBase;
    protected $_filenamePath;
    protected $_filenameExt;
    protected $_basePath;
    
    
    public function __construct($var_file, $extention = 'dat')
    {
        $this->_basePath = App::getConfig('Lib/VarFile/basepath');
        
        $filename = $this->_getFilename($var_file, $extention);
        
        $this->_fileObject = new Lib_FileHandeling($filename);
    }
    
    
    protected function _getFilename($var_file, $extention) { }
}

