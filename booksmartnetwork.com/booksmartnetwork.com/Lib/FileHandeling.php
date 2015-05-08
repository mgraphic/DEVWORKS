<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_FileHandeling Extends RecursiveDirectoryIterator
{
    public function __construct($filename = NULL, $flags = NULL)
    {
        parent::__construct($filename, $flags);
        
        return $this;
    }
    
    
    public function read($filename)
    {
        if (self::_isReadable($filename))
        {
            return file_get_contents($filename);
        }
        
        return false;
    }
    
    
    public function write($filename, $data, $append = false)
    {
        if (!self::_checkPath($filename))
        {
            throw new Exception ("$filename: Unable to write file");
        }
        
        $flags = ($append) ? FILE_APPEND : 0;
        
        return file_put_contents($filename, $data, $flags);
    }
    
    
    public function delete($filename)
    {
        if (!self::_isWritable($filename))
        {
            throw new Exception ("$filename: Unable to delete dir/file");
        }
        
        // Todo:
        // Add recurcive delete if $filename is a dir
        
        return unlink($filename);
    }
    
    
    protected function _checkPath($path) { }
    
    
    protected function _makeDir($dir) { }
    
    
    protected function _getFilenamePath($filename)
    {
        return dirname($filename);
    }
    
    
    protected function _isReadable($filename)
    {
        return DirectoryIterator($filename)->isReadable();
    }
    
    
    protected function _isWritable($filename)
    {
        return DirectoryIterator($filename)->isWritable();
    }
}

