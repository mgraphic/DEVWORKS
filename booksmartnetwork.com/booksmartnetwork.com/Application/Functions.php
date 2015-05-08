<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_Functions
{
    public function __construct(){}
    
    
    public static function arrayMergeRecurcive()
    {
        $result = array();
        
        $args = func_get_args();
        
        foreach ($args as $arg)
        {
            foreach ($arg as $key => $value)
            {
                if (is_numeric($key))
                {
                    // Renumber numeric keys as array_merge() does.
                    $result[] = $value;
                }
                else if (is_array($value) AND isset($result[$key]) AND is_array($result[$key]))
                {
                    // Recurse only when both values are arrays.
                    $result[$key] = self::arrayMergeRecurcive($result[$key], $value);
                }
                else
                {
                    // Otherwise, use the latter value.
                    $result[$key] = $value;
                }
            }
        }
        
        return $result;
    }
    
    
    public static function insertSQLArray($table, Array $data, Application_DataAccess &$dbObject, $clearCache = true)
    {
        $keys = $values = array();
        
        foreach ($data AS $key => $value)
        {
            $keys[] = "`$key` =?";
            
            $values[] = $value;
        }
        
        $result = $dbObject->execute("
            INSERT INTO `$table`
            SET " . implode(", ", $keys) . "
        ", $values);
        
        if ($clearCache) $dbObject->clearCache();
        
        return $result;
    }
    
    
    public static function buildQuickForm($idName, $label, Array $data, $method = 'post', Array $options = array())
    {
        $_method = (strtolower($method) == 'get') ? 'get' : 'post';
        
        $_form = array('form' => new HTML_QuickForm2($idName, $_method, array_merge(array('accept-charset' => 'UTF-8'), $options)));
        
        $_form['container'] = $_form['form']->addElement('fieldset')->setLabel($label);
        
        $_userInput = array();
        
        foreach ($data AS $key => $value)
        {
            $_inputType = App::clean($value['input_type'], TYPE::_LSTR);
            
            $_elementOptions = (is_array($value['options'])) ? $value['options'] : array();
            
            switch ($_inputType)
            {
                case 'multi-select':
                    $_elementOptions['multiple'] = 'multiple';
                    
                case 'select':
                    $_type = 'select';
                    
                break;
                case 'multi-text':
                    $_type = 'textarea';
                    
                break;
                default:
                    $_type = $_inputType;
            }
            
            switch ($value['data_type'])
            {
                case TYPE::_UINT:
                case TYPE::_UFLOAT:
                    $_eval = '%d > 0';
                break;
                case TYPE::_INT:
                case TYPE::_FLOAT:
                    $_eval = '%d <> 0';
                break;
                default:
                    $_eval = 'strlen(\'%s\') > 0';
            }
            
            App::clean(($_method == 'get') ? 'g' : 'p', $key, (isset($value['data_type'])) ? $value['data_type'] : TYPE::_STR);
            
            $_element = $_form['container']->addElement($value['input_type'], $key, $_elementOptions);
            
            switch ($_type)
            {
                case 'select':
                    $array = array('' => 'select...');
                    
                    $value['values'] = (array)$value['values'];
                    
                    foreach ($value['values'] AS $k => $v) $array[$k] = $v;
                    
                    $_element->loadOptions($array);
                    
                    $_eval = 'in_array(\'%s\', array_keys((array)$value["values"]))';
                    
                    if (!isset($value['values'][""])) $_eval .= ' AND App::getRequest($key) != ""';
                break;
            }
            
            if ($value['eval']) $_eval = $value['eval'];
            
            $_eval = eval(sprintf("return ($_eval);", addslashes(App::getRequest($key))));
            
            $_element->setValue((App::GPCExists($key) AND $_eval) ? App::getRequest($key) : '');
            
            $_element->addFilter('trim');
            
            if ($value['required']) $_element->addRule('required', 'required');
            
            if ($value['title']) $_element->setLabel($value['title'] . ':');
            
            $_userInput[$key] = App::getRequest($key);
        }
        
        $_form['submit'] = $_form['container']->addGroup();
        
        $_form['submit']->addSubmit('submit', array('value' => 'Submit'));
        
        $_form['structure'] = $_data;
        
        $_form['input'] = $_userInput;
        
        return $_form;
    }
}


