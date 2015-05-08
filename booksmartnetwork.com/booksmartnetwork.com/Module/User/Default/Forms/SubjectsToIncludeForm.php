<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  $_data = array(
    'ssid' => array(
        'data_type' => TYPE::_UINT,
        'input_type' => 'hidden',
        'required' => false,
        'eval' => '%d > 0',
    ),
  );
  
  $_data = $_data + $this->getStaticFormArray('SubjectNeed');
  
  $_form = Application_Functions::buildQuickForm('SubjectsToIncludeForm', 'Subjects To Include', $_data, 'post', array(
      'action' => $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=new_subject', 'SSL'),
  ));
  
  $_form['container']->removeChild($_form['submit']);
  
  $_form['submit'] = $_form['container']->addGroup();
  
  $_form['submit']->addSubmit('finish', array('value' => 'Finish'));
  
  $_form['submit']->addSubmit('continue', array('value' => 'Add Another Subject'));
  
  $_form['submit']->addReset('cancel', array('value' => 'Cancel', 'onclick' => $this->getLinkBuilder()->url('user/default/curriculumNeeds')));
  
  
  $this->setSubjectsToIncludeForm($_form);
  
 