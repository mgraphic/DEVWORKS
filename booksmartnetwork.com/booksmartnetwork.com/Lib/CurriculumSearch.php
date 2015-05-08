<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_CurriculumSearch Extends Application_Extender
{
    /* Handler properties */
    private $_db;
    
    
    /* Other properties */
    private $_attrvaluetableName;
    private $_currsubjecttableName;
    protected $_memTable;
    
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_memTable = 'mem_' . strtolower(__CLASS__);
        
        $this->_check();
    }
    
    
    private function _check()
    {
        static $_init = false;
        
        if (!$_init)
        {
            $_init = true;
            
            $this->_db = App::getInstance('Application_DataAccess', array(__CLASS__));
            
            $this->_attrvaluetableName = App::getConfig('Lib/Curriculum/SearchReference/sql_tables/attrvaluetable/name');
            
            $this->_currsubjecttableName = App::getConfig('Lib/Curriculum/SearchReference/sql_tables/currsubjecttable/name');
            
            #if ($this->_db->tableExists($this->_memTable)) return;
            
            $this->_buildReferenceData();
        }
    }
    
    
    private function _buildReferenceData()
    {
        /*$this->_db->execute("
        	DROP TABLE IF EXISTS {$this->_memTable};
        	
        	CREATE TABLE {$this->_memTable} (
        		curriculum_id int(11) unsigned NOT NULL,
        		score decimal(20,20) NOT NULL,
        		PRIMARY KEY (curriculum_id)
        	) ENGINE=MEMORY;
        ");*/
        
        $_subjectSearchArray =
        $_scenarioAttributeValueSearchArray =
        $_subjectScoresArray =
        $_attributeValueScoresArray =
        $_attributeWeightArray =
        $_curriculumScoresArray =
        array();
        
        
        
        
        $_subjects = $this->getStaticValues('subject');
        
        $_attributes = $this->getStaticAttributes('CurriculumNeed');
        
        $_values = array();
        
        foreach ($_attributes AS $id => $value) $_values[$id] = $this->getStaticValues($value['attribute']);
        
        $_scenarios = array();
        
        
        $_subjects = $_attributes = $_values = $_scenarios = array();
        
        $s = $this->getStaticValues('subject');
        while (list($id, $value) = each($s)) $_subjects[] = $id;
        
        $a = $this->getStaticAttributes('CurriculumNeed');
        while (list($id, $value) = each($a)) $_attributes[$id] = $value;
        
        while (list($id, $value) = each($_attributes)) $_values[$id] = $this->getStaticValues($value['attribute']);
        
        while (list($id, $value) = each($_values)) foreach ($value AS $v) $_scenarios[$id][] = $v['id'];
        
        #$_scenarios = $this->_getCartesian($_scenarios);
        
        
        foreach(array('_subjects','_attributes','_values','_scenarios') AS $v)print_r($$v);
        #foreach(array('_values') AS $v)print_r($$v);
        /*
        foreach ($_attributes AS $aid => $attribute)
        {
            foreach ($_values)
        }
        */
        #foreach(array('_subjects','_attributes','_values') AS $v)echo"\$$v = unserialize('".serialize($$v)."');\n\n";
        return;
        
        
        
        $_curriculumScoresArray = array();
        
        $_subjects = $this->getStaticValues('subject');
        
        while (list($_sId, $_sValue) = each($_subjects))
        {
            $_subjectScores = $this->_db->execute("
            	SELECT score, curriculum_id
            	FROPM {$this->_currsubjecttableName}
            	WHERE subject_id =?
            ", array($_sId));
            
            foreach ($_subjectScores AS $_sScore)
            {
                $_sWeight = $_sScore['score'];
                
                $_cId = $_sScore['curriculum_id'];
                
                $_curriculumScoresArray[$_cId] = $_sWeight;
                
                
            }
        }
    }
    private function _getCartesian(Array $array)
    {
        $_result = array();
        
        foreach ($array AS $_aId => $_options)
        {
            if (empty($_options)) continue;
            
            
            if (empty($_result))
            {
                foreach ($_options AS $_vId) $_result[] = array($_aId => $_vId);
            }
            else
            {
                $_append = array();
                
                foreach ($_result AS &$_product)
                {
                    $_product[$_aId] = array_shift($_options);
                    
                    $_copy = $_product;
                    
                    foreach($_options AS $_item)
                    {
                        $_copy[$_aId] = $_item;
                        
                        $_append[] = $_copy;
                    }
                    
                    array_unshift($_options, $_product[$_aId]);
                }
                
                $_result = array_merge($_result, $_append);
            }
        }
        
        return $_result;
    }
    
    
    
    
    
    public function rebuild()
    {
        $this->_buildReferenceData();
    }
}

