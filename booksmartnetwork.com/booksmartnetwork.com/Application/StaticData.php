<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_StaticData
{
    /* Handler properties */
    protected $_db;
    
    
    /* Other properties */
    #protected $_entity;
    protected $_attributesTable;
    protected $_correlationsTable;
    protected $_entitiesTable;
    protected $_valuesTable;
    
    
    
    public function __construct()
    {
        $this->_db = App::getInstance('Application_DataAccess', array(__CLASS__));
        
        $this->_attributesTable = App::getConfig('Application/StaticData/sql_tables/attributestable/name');
        
        $this->_attributestableCache = App::getConfig('Application/StaticData/sql_tables/attributestable/cache');
        
        $this->_correlationsTable = App::getConfig('Application/StaticData/sql_tables/correlationstable/name');
        
        $this->_correlationstableCache = App::getConfig('Application/StaticData/sql_tables/correlationstable/cache');
        
        $this->_entitiesTable = App::getConfig('Application/StaticData/sql_tables/entitiestable/name');
        
        $this->_entitiestableCache = App::getConfig('Application/StaticData/sql_tables/entitiestable/cache');
        
        $this->_valuesTable = App::getConfig('Application/StaticData/sql_tables/valuestable/name');
        
        $this->_valuestableCache = App::getConfig('Application/StaticData/sql_tables/valuestable/cache');
    }
    
    
    public function getStaticEntityId($entity)
    {
        static $_cache = array();
        
        if (isset($_cache[$entity])) return $_cache[$entity];
        
        $_entity = $this->_db->execute("
        	SELECT entity_id
        	FROM {$this->_entitiesTable}
        	WHERE entity LIKE ?
        ", array($entity), $this->_entitiestableCache);
        
        $_cache[$entity] = App::clean($_entity[0]['entity_id'], TYPE::_UINT);
        
        return $_cache[$entity];
    }
    
    
    public function getStaticAttributes($entity)
    {
        if (!is_numeric($entity)) $entity = $this->getStaticEntityId($entity);
        
        static $_cache = array();
        
        if (isset($_cache[$entity])) return $_cache[$entity];
        
        $_entityId = App::clean($entity, TYPE::_UINT);
        
        $_results = $this->_db->execute("
            SELECT attributes_id, attribute, title, data_type, input_type, required, weight
            FROM {$this->_attributesTable}
            WHERE entities_id = $_entityId
            ORDER BY sort, title, attribute, attributes_id
        ", NULL, $this->_attributestableCache);
        
        $_attributes = array();
        
        foreach ($_results AS $result)
        {
            $_attributes[App::clean($result['attributes_id'], TYPE::_UINT)] = array(
                'id' => App::clean($result['attributes_id'], TYPE::_UINT),
                'attribute' => $result['attribute'],
                'title' => $result['title'],
                'data_type' => constant("TYPE::_{$result['data_type']}"),
                'input_type' => $result['input_type'],
                'required' => (bool)$result['required'],
                'weight' => (float)$result['weight'],
            );
        }
        
        $_cache[$entity] = $_attributes;
        
        return $_cache[$entity];
    }
    
    
    public function getStaticAttributeId($attribute)
    {
        static $_cache = array();
        
        if (isset($_cache[$attribute])) return $_cache[$attribute];
        
        $_attribute = $this->_db->execute("
        	SELECT attributes_id
        	FROM {$this->_attributesTable}
        	WHERE attribute LIKE ?
        ", array($attribute), $this->_attributestableCache);
        
        $_cache[$attribute] = App::clean($_attribute[0]['attributes_id'], TYPE::_UINT);
        
        return $_cache[$attribute];
    }
    
    
    public function getStaticValues($attribute)
    {
        if (!is_numeric($attribute)) $attribute = $this->getStaticAttributeId($attribute);
        
        static $_cache = array();
        
        if (isset($_cache[$attribute])) return $_cache[$attribute];
        
        $_attributeId = App::clean($attribute, TYPE::_UINT);
        
        $_results = $this->_db->execute("
            SELECT values_id, keyname, value
            FROM {$this->_valuesTable}
            WHERE attributes_id = $_attributeId
            ORDER BY sort, value, keyname, values_id
        ", NULL, $this->_valuestableCache);
        
        $_values = array();
        
        foreach ($_results AS $result)
        {
            $_values[App::clean($result['values_id'], TYPE::_UINT)] = array(
                'id' => App::clean($result['values_id'], TYPE::_UINT),
                'keyname' => (strlen($result['keyname']) <= 0) ? App::clean($result['values_id'], TYPE::_UINT) : $result['keyname'],
                'value' => (strlen($result['value']) <= 0) ? $result['keyname'] : $result['value'],
            );
        }
        
        $_cache[$attribute] = $_values;
        
        return $_cache[$attribute];
    }
    
    
    public function getStaticFormArray($entity)
    {
        static $_cache = array();
        
        if (isset($_cache[$entity])) return $_cache[$entity];
        
        $_dataArray = array();
        
        $_attributes = $this->getStaticAttributes($entity);
        
        foreach ($_attributes AS $id => $value)
        {
            $_attribute = $value['attribute'];
            
            $_dataArray[$_attribute] = $value;
            
            if (in_array($value['input_type'], array('Select', 'Multi-Select')))
            {
                $_values = $this->getStaticValues($id);
                
                $_array = array();
                
                foreach ($_values AS $_value) $_array[$_value['id']] = $_value['value'];
                
                $_dataArray[$_attribute]['values'] = $_array;
            }
        }
        
        $_cache[$entity] = $_dataArray;
        
        return $_cache[$entity];
    }
}

