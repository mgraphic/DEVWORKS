<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  /*
  $_data = array(
    'scenario_lable' => array(
        'title' => 'Short Name for Need',
        'data_type' => TYPE::_STR,
        'input_type' => 'text',
        'required' => true,
        'eval' => 'strlen(\'%s\') > 0',
    ),
    
    'school_type' => array(
        'title' => 'School Type',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["school_type"]["values"]))',
        'values' => array(
            'HOME' => 'Homeschool',
            'PRIVATE' => 'Private',
            'PUBLIC' => 'Public',
        ),
    ),
    
    'philosophy' => array(
        'title' => 'Education Philosophy',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["philosophy"]["values"]))',
        'values' => array(
            'CLASSICAL' => 'Classical / Charlotte Mason',
            'ECLECTIC' => 'Eclectic',
            'TRADITIONAL' => 'Traditional',
        ),
    ),
    
    'emphasis' => array(
        'title' => 'Religious Emphasis',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["emphasis"]["values"]))',
        'values' => array(
            'CHRISTIAN' => 'Christian Emphasis',
            'NON-RELIGIOUS' => 'Non-Religious Emphasis',
            'UNCONSTRAINED' => 'Unconstrained',
        ),
    ),
    
    'grade_level' => array(
        'title' => 'Grade Level',
        'data_type' => TYPE::_INT,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%d\', array_keys($_data["grade_level"]["values"]))',
        'values' => array(
            '-1' => 'Pre-K',
            '0' => 'K',
            '1' => '1st Grade',
            '2' => '2nd Grade',
            '3' => '3rd Grade',
            '4' => '4th Grade',
            '5' => '5th Grade',
            '6' => '6th Grade',
            '7' => '7th Grade',
            '8' => '8th Grade',
            '9' => '9th Grade',
            '10' => '10th Grade',
            '11' => '11th Grade',
            '12' => '12th Grade',
            '13' => 'Graduate',
            '14' => 'Post-Graduate',
            '15' => 'Doctorate',
        ),
    ),
    
    'student_age' => array(
        'title' => 'Avg. Student Age',
        'data_type' => TYPE::_UINT,
        'input_type' => 'text',
        'required' => true,
        'eval' => '%d > 0',
    ),
    
    'learning_style' => array(
        'title' => 'Dominant Learning Style',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["learning_style"]["values"]))',
        'values' => array(
            'AUDIO' => 'Audio',
            'COMBINATION' => 'Combination',
            'KINESTHETIC' => 'Kinesthetic',
            'VISUAL' => 'Visual',
        ),
    ),
    
    'reading_level' => array(
        'title' => 'Avg. Reading Level',
        'data_type' => TYPE::_INT,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%d\', array_keys($_data["reading_level"]["values"]))',
        'values' => array(
            '-1' => 'Pre-K',
            '0' => 'K',
            '1' => '1st Grade',
            '2' => '2nd Grade',
            '3' => '3rd Grade',
            '4' => '4th Grade',
            '5' => '5th Grade',
            '6' => '6th Grade',
            '7' => '7th Grade',
            '8' => '8th Grade',
            '9' => '9th Grade',
            '10' => '10th Grade',
            '11' => '11th Grade',
            '12' => '12th Grade',
            '13' => 'Graduate',
            '14' => 'Post-Graduate',
            '15' => 'Doctorate',
        ),
    ),
    
    'format' => array(
        'title' => 'School Format',
        'data_type' => TYPE::_STR,
        'input_type' => 'select',
        'required' => true,
        'eval' => 'in_array(\'%s\', array_keys($_data["format"]["values"]))',
        'values' => array(
            'CORRESPONDENCE' => 'Correspondence',
            'GROUP_INSTRUCTION' => 'Group Instruction',
            'INDIVIDUAL_INSTRUCTION' => 'Individual Instruction',
            'ONLINE' => 'Online',
        ),
    ),
  );
  
  
  $_form = array('form' => new HTML_QuickForm2('SearchScenarioForm', 'post', array(
      'action' => $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=new_scenario', 'SSL'),
  )));
  
  $_form['container'] = $_form['form']->addElement('fieldset')->setLabel('Curriculum Search Scenario');
  
  
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
              $array = array('' => 'select...');
              
              foreach ((array)$value['values'] AS $k => $v) $array[$k] = $v;
              
              $_element->loadOptions($array);
          break;
      }
      
      $_userInput[$key] = $this->getRequest($key);
  }
  
  
  $_form['container']->addElement('submit', NULL, array('value' => 'Submit'));
  
  $_form['structure'] = $_data;
  
  $_form['input'] = $_userInput;
  */
  
  
  $_form = Application_Functions::buildQuickForm('SearchScenarioForm', 'Curriculum Search Scenario', $this->getStaticFormArray('CurriculumNeed'), 'post', array(
      'action' => $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=new_scenario', 'SSL'),
  ));
  
  
  $this->setSearchScenarioForm($_form);
  
  

