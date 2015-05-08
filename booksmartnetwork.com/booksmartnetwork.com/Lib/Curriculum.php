<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Lib_Curriculum Extends Lib_CurriculumSearch
{
    /* Handler properties */
    protected static $_db;
    
    
    /* Other properties */
    protected static $_currscenariotableName;
    protected static $_currscenariotableCache;
    protected static $_scenariosubjectstableName;
    protected static $_scenariosubjectstableCache;
    
    protected $_accountId;
    
    
    
    public function __construct($accountId)
    {
        parent::__construct();
        
        self::_init();
        
        $this->_accountId = $this->clean($accountId, TYPE::_UINT);
    }
    
    
    private static function _init()
    {
        static $_init = false;
        
        if (!$_init)
        {
            self::$_db = App::getInstance('Application_DataAccess', array(__CLASS__));
            
            self::$_currscenariotableName = App::getConfig('Lib/Curriculum/sql_tables/currscenariotable/name');
            
            self::$_currscenariotableCache = App::getConfig('Lib/Curriculum/sql_tables/currscenariotable/cache');
            
            self::$_scenariosubjectstableName = App::getConfig('Lib/Curriculum/sql_tables/scenariosubjectstable/name');
            
            self::$_scenariosubjectstableCache = App::getConfig('Lib/Curriculum/sql_tables/scenariosubjectstable/cache');
            
            $_init = true;
        }
    }
    
    
    public function isValidScenarioId($ssid)
    {
        $_result = $this->_getCurriculumScenarioByConstraints(array('curriculum_scenario_id'), array('curriculum_scenario_id' => $ssid));
        
        return (count($_result) == 1) ? true : false;
    }
    
    
    public function getScenarioSubjectArray(Array $select = NULL, Array $by = NULL)
    {
        $_id = $this->_getCurriculumScenarioByConstraints(array('curriculum_scenario_id'));
        
        $_select = (!empty($select)) ? $select : array('*');
        
        $_by = (!empty($select)) ? $by : array();
        
        return $this->_getScenarioSubjectByConstraints($_id['curriculum_scenario_id'], $_select, $_by);
    }
    
    
    protected function _getScenarioSubjectByConstraints($parentId, Array $selectConstraints, Array $byConstraints = array())
    {
        $_table = self::$_scenariosubjectstableName;
        
        $_cache = self::$_scenariosubjectstableCache;
        
        $_fields = implode(", ", $selectConstraints);
        
        $_where = (count($byConstraints) > 0) ? "AND " . implode(" =? AND ", array_keys($byConstraints)) . " =?" : "";
        
        $_parentId = $this->clean($parentId, TYPE::_UINT);
        
        $_result = self::$_db->execute("
            SELECT $_fields
            FROM $_table
            WHERE curriculum_scenario_id =?
            $_where
        ", array_merge(array($_parentId), array_values($byConstraints)), $_cache);
        
        return $_result;
    }
    
    
    public function getCurriculumScenarioArray(Array $select = NULL, Array $by = array())
    {
        $_select = (!empty($select)) ? $select : array('*');
        
        $_by = (!empty($select)) ? $by : array();
        
        return $this->_getCurriculumScenarioByConstraints($_select, $_by);
    }
    
    
    protected function _getCurriculumScenarioByConstraints(Array $selectConstraints, Array $byConstraints = array())
    {
        $_table = self::$_currscenariotableName;
        
        $_cache = self::$_currscenariotableCache;
        
        $_fields = implode(", ", $selectConstraints);
        
        $_where = (count($byConstraints) > 0) ? "AND " . implode(" =? AND ", array_keys($byConstraints)) . " =?" : "";
        
        $_result = self::$_db->execute("
            SELECT $_fields
            FROM $_table
            WHERE account_id =?
            $_where
        ", array_merge(array($this->_accountId), array_values($byConstraints)), $_cache);
        
        return $_result;
    }
    
    
    public static function insertCurriculumScenario($account_id, Array $data)
    {
        self::_init();
        
        if ($account_id <= 0) return false;
        
        $data['account_id'] = $account_id;
        
        $_columns = array(
            'scenario_lable' => TYPE::_STR,
            'account_id' => TYPE::_UINT,
            'school_type' => TYPE::_STR,
            'philosophy' => TYPE::_STR,
            'emphasis' => TYPE::_STR,
            'format' => TYPE::_STR,
            'grade_level' => TYPE::_INT,
            'student_age' => TYPE::_UINT,
            'learning_style' => TYPE::_STR,
            'reading_level' => TYPE::_INT,
        );
        
        $_data = App::clean($data, $_columns);
        
        Application_Functions::insertSQLArray(self::$_currscenariotableName, $_data, self::$_db);
        
        return self::$_db->getInsertId();
    }
    
    
    public static function insertScenarioSubject($parent_id, Array $data)
    {
        self::_init();
        
        if ($parent_id <= 0) return false;
        
        $data['curriculum_scenario_id'] = $parent_id;
        
        $_columns = array(
            'curriculum_scenario_id' => TYPE::_UINT,
            'discipline' => TYPE::_STR,
            'subject' => TYPE::_STR,
        );
        
        $_data = App::clean($data, $_columns);
        
        Application_Functions::insertSQLArray(self::$_scenariosubjectstableName, $_data, self::$_db);
        
        return self::$_db->getInsertId();
    }
}

