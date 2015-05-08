<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_User_Default_Processor Extends Module_Processor Implements Module_Helper_iProcessor
{
    public function process()
    {
        if (!in_array($this->_currentModule['page'], array('Login'))) $this->_setAuthorized(array('USER'));
        
        return parent::process();
    }
    
    
    public function processLogin()
    {
        if (empty($this->_currentStack['action'])) return $this;
        
        $this->clean('p', array(
            'email' => TYPE::_STR,
            'password' => TYPE::_STR,
        ));
        
        if ($this->_currentStack['action'] == 'logout')
        {
            $this->getSession('user')->reset();
            
            $this->_setMessage('You have been sucessfully logged out', NULL, true);
            
            return $this;
        }
        
        if ($this->GPCExists('email') AND $this->GPCExists('password'))
        {
            if (!$this->getSession('user')->loginEmail($this->getRequest('email'), $this->getRequest('password')))
            {
                $this->_setMessage('Password or email is invalid');
            }
            else
            {
                $this->_setMessage('You have been sucessfully logged in', NULL, $this->getLinkBuilder()->url('user/default/home', '', 'SSL'));
            }
        }
        else
        {
            $this->_setMessage('Missing required fields');
        }
        
        return $this;
    }
    
    
    public function processCurriculumNeeds()
    {
        if (empty($this->_currentStack['action'])) return $this;
        
        $this->clean('r', array(
            'ssid' => TYPE::_UINT,
            'continue' => TYPE::_STR,
        ));
        
        $_accountId = $this->getSession('user')->getInfo('id');
        
        $_ssid = $this->getRequest('ssid');
        
        
        switch ($this->_currentStack['action'])
        {
            case 'search':
                $_form = $this->getForm('Module_User_Default_Forms_CurriculumSearchForm');
                
                $_curriculum = App::getInstance('Lib_Curriculum', array($_accountId));
                
                if ($_form['form']->validate() AND $_curriculum->isValidScenarioId($_ssid))
                {
                }
            break;
            case 'new_scenario':
                $_form = $this->getForm('Module_User_Default_Forms_SearchScenarioForm');
                
                if ($_form['form']->validate())
                {
                    $id = Lib_Curriculum::insertCurriculumScenario($_accountId, $_form['input']);
                    
                    $this->_setMessage('Scenario has been saved', NULL, $this->getLinkBuilder()->url('user/default/curriculumNeeds', "action=new_subject&ssid=$id"));
                }
            break;
            case 'new_subject':
                $_form = $this->getForm('Module_User_Default_Forms_SubjectsToIncludeForm');
                
                $_curriculum = App::getInstance('Lib_Curriculum', array($_accountId));
                
                if ($_form['form']->validate() AND $_curriculum->isValidScenarioId($_ssid))
                {
                    $_label = $_curriculum->getCurriculumScenarioArray(array('scenario_lable'), array('curriculum_scenario_id' => $_ssid));
                    
                    Lib_Curriculum::insertScenarioSubject($_ssid, $_form['form']->getValue());
                    
                    $_message = "Subject has been saved for label '{$_label[0]['scenario_lable']}'";
                    
                    if ($this->GPCExists('continue'))
                    {
                        $this->_setMessage($_message, NULL, $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=new_subject&ssid=' . $_ssid));
                    }
                    else
                    {
                        $this->_setMessage($_message, NULL, $this->getLinkBuilder()->url('user/default/curriculumNeeds'));
                    }
                }
            break;
        }
        
        return $this;
    }
}

