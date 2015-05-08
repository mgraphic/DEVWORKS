<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_Presenter Extends Application_View
{
    protected $_templates = array(
        'pre' => array(),
        'post' => array(),
    );
    
    protected $_error = array();
    
    private $_renderOutput = true;
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_prepareTemplates();
    }
    
    
    public function present()
    {
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        if (method_exists("Module_{$_view}_{$_path}_Presenter", 'page' . $this->_currentModule['page']))
        {
            call_user_func(array($this, 'page' . $this->_currentModule['page']));
            
            if ($this->_renderOutput) $this->_render();
        }
        else
        {
            throw new Exception('Method does not exist');
        }
        
        return $this;
    }
    
    
    protected function _render()
    {
        foreach ((array)$this->_templates['pre'] AS $file)
        {
            if (is_readable($file)) include $file;
        }
        
        $this->getHtml("Module_{$this->_currentModule[view]}_{$this->_currentModule[path]}_View_{$this->_currentModule[page]}");
        
        foreach ((array)$this->_templates['post'] AS $file)
        {
            if (is_readable($file)) include $file;
        }
        
        return $this;
    }
    
    
    protected function _prepareTemplates()
    {
        $_configPath = "Module/{$this->_currentModule[view]}/{$this->_currentModule[path]}/templates";
        
        $_templates = $this->getConfig($_configPath);
        
        if (!empty($_templates))
        {
            foreach ((array)$_templates AS $_position)
            {
                if (in_array($_position['name'], array('pre', 'post')))
                {
                    $_value = $this->getConfig("{$_configPath}/{$_position[name]}/template_file");
                    
                    if (is_array($_value))
                    {
                        foreach ($_value AS $_template)
                        {
                            if (is_string($_template) AND !empty($_template))
                            {
                                $this->_templates[$_position['name']][] = $_template;
                            }
                        }
                    }
                    else if (is_string($_value) AND !empty($_value))
                    {
                        $this->_templates[$_position['name']][] = $_value;
                    }
                }
            }
        }
        
        return $this;
    }
    
    
    public function disableOutputRender()
    {
        $this->_renderOutput = false;
    }
    
    
    public function enableOutputRender()
    {
        $this->_renderOutput = true;
    }
    
    
    public function getTitle()
    {
        $_title = $this->getConfig("Module/{$this->_currentModule[view]}/{$this->_currentModule[path]}/title");
        
        $_pageName = ucwords(str_replace('_', ' ', $this->underscore($this->_currentModule['page'])));
        
        $this->_outputString = (!empty($_title)) ? sprintf($_title, $_pageName) : $_pageName;
        
        return $this;
    }
    
    
    public function getError()
    {
        return $this->_error;
    }
}

