<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Module_User_Default_Presenter Extends Module_Presenter Implements Module_Helper_iPresenter
{
    public function present()
    {
        if (!in_array($this->_currentModule['page'], array('Login'))) $this->_setAuthorized(array('USER'));
        
        return parent::present();
    }
    
    
    public function pageHome(){}
    
    
    public function pageLogin()
    {
        $this->clean('p', array(
            'email' => TYPE::_STR,
        ));
    }
    
    
    public function pageCurriculumNeeds()
    {
        $_curriculum = App::getInstance('Lib_Curriculum', array($this->getSession('user')->getInfo('id')));
        
        switch ($this->_currentStack['action'])
        {
            case 'search':
                $_form = $this->getForm('Module_User_Default_Forms_CurriculumSearchForm');
                
                $this->setVar('form', $_form);
            break;
            case 'new_subject':
                $_form = $this->getForm('Module_User_Default_Forms_SubjectsToIncludeForm');
                
                $this->setVar('form', $_form);
            break;
            case 'subjects':
                #$_data = $_curriculum->getScenarioSubjectArray();
            break;
            case 'new_scenario':
                $_form = $this->getForm('Module_User_Default_Forms_SearchScenarioForm');
                #$_form = $this->buildQuickForm('SearchScenarioForm', 'Curriculum Search Scenario', $this->getStaticFormArray('CurriculumNeed'), 'post', array(
                #    'action' => $this->getLinkBuilder()->url('user/default/curriculumNeeds', 'action=new_scenario', 'SSL'),
                #));
                $this->setVar('form', $_form);
            break;
            default:
                #$_data = $_curriculum->getCurriculumScenarioArray();
        }
    }
}

