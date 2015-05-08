<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
  $_data = array(
    'ssid' => array(
        'title' => 'Search Scenario',
        'data_type' => TYPE::_UINT,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["ssid"]["values"]))',
        'values' => array(),
    ),
    
    'provider' => array(
        'title' => 'Limit To Provider',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => false,
        'eval' => 'in_array(\'%s\', array_keys($_data["provider"]["values"]))',
        'values' => array(
            '' => 'ALL',
        ),
    ),
    
    'published' => array(
        'title' => 'Published After Yr',
        'data_type' => TYPE::_UINT,
        'input_type' => 'text',
        'required' => false,
        'eval' => '%1$d > 1800 AND %1$d <= date("Y")',
    ),
    
    'lesson_plans' => array(
        'title' => 'Lesson Plans Included',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["lesson_plans"]["values"]))',
        'values' => array(
            'PREFERRED' => 'Preferred',
        ),
    ),
    
    'grade_adapt' => array(
        'title' => 'Multi-Grade Adaptable',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["grade_adapt"]["values"]))',
        'values' => array(
            'ESSENTIAL' => 'Essential',
        ),
    ),
  );
  
  
  // Get scenario options:
  $_curriculum = App::getInstance('Lib_Curriculum', array($this->getSession('user')->getInfo('id')));
  
  $_options = $_curriculum->getCurriculumScenarioArray(array('scenario_lable AS label', 'curriculum_scenario_id AS id'));
  
  foreach ($_options AS $value) $_data['ssid']['values'][$value['id']] = $value['label'];
  
  
  $_form = array('form' => new HTML_QuickForm2('CurriculumSearchForm', 'post', array(
      'action' => $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=search'),
  )));
  
  $_form['container'] = $_form['form']->addElement('fieldset')->setLabel('Curriculum Search');
  
  
  $_userInput = array();
  
  foreach ($_data AS $key => $value)
  {
      if (isset($value['data_type'])) $this->clean('p', $key, $value['data_type']);
      
      $eval = (isset($value['eval'])) ? eval(sprintf("return ({$value['eval']});", addslashes($this->getRequest($key)))) : false;
      
      $_element = $_form['container']->addElement($value['input_type'], $key);
      
      $_element->setValue(($this->GPCExists($key) AND $eval) ? $this->getRequest($key) : '');
      
      $_element->addFilter('trim');
      
      if ($value['required']) $_element->addRule('required', 'required');
      
      if ($value['title']) $_element->setLabel($value['title'] . ':');
      
      switch ($value['input_type'])
      {
          case 'select':
              $array = array();
              
              foreach ((array)$value['values'] AS $k => $v) $array[$k] = $v;
              
              $_element->loadOptions($array);
          break;
      }
      
      $_userInput[$key] = $this->getRequest($key);
  }
  
  
  $_form['container']->addElement('submit', NULL, array('value' => 'Search'));
  
  $_form['structure'] = $_data;
  
  $_form['input'] = $_userInput;
  
  
  $this->setCurriculumSearchForm($_form);
  
  

