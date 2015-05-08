<?php
  
  if (__APP !== true) die(header('HTTP/1.1 403 Forbidden'));
  
  
  
Class Application_Controller Extends Application_Extender
{
    const ACTION_TYPE_PROCESSOR = 1;
    const ACTION_TYPE_SERVICE   = 2;
    const ACTION_TYPE_PRESENTER = 3;
    
    private static $_thisInstance;
    
    private static $_thisRun = false;
    
    /* Handler properties */
    protected $_stack;
    
    /* Other properties */
    protected $_previousStack;
    protected $_currentStack;
    
    
    public function __construct()
    {
        if (isset(self::$_thisInstance)) throw new Exception(__CLASS__ . ' is not supposed to be instantiated from the global scope because it is a Singleton class.');
        
        parent::__construct();
        
        $this->newLinkBuilder('Lib_LinkBuilder');
        
        if (!$this->getUri())
        {
            $url = $this->getLinkBuilder()->url('public/default/home');
            
            header("Location: $url");
            
            exit;
        }
        
        $this->_stack = App::getSingleton('Application_NavStack');
        
        /**  Process route actions  **/
        $this->_previousStack = $this->_stack->getPrevious();
        
        $this->_currentStack = $this->_getRoute();
    }
    
    
    private function __clone() { }
    
    
    public static function singleton()
    {
        if (!isset(self::$_thisInstance))
        {
            $_class = __CLASS__;
            self::$_thisInstance = new $_class;
        }
        
        return self::$_thisInstance;
    }
    
    
    public function getCurrentStack()
    {
        return (array)$this->_currentStack;
    }
    
    
    public function getPreviousStack()
    {
        return (array)$this->_previousStack;
    }
    
    
    public function run()
    {
        if (!self::$_thisRun)
        {
            self::$_thisRun = true;
            
            $this->_navPlanner();
            
            $this->_stack->setNext($this->_currentStack);
            
            /**  Set new session token after processing has taken place  **/
            $this->setSession('token', $this->_currentStack['token']);
        }
    }
    
    
    public function errorView($message = NULL, $statusCode = NULL)
    {
        if (!empty($message)) $this->_currentStack['status']['message'] = $message;
        
        if (!empty($statusCode)) $this->_currentStack['status']['code'] = $statusCode;
        
        $this->_currentStack['module'] = 'public/default/error';
        
        $this->_presentView();
        
        $this->setTempStack(NULL, true);
        
        exit;
    }
    
    
    public function redirect($url)
    {
        if (self::$_thisRun)
        {
            $this->errorView('Unable to use redirect method when the Controller NavPlanner is active');
        }
        else
        {
            $this->_redirect($url, true);
        }
    }
    
    
    protected function _redirect($url, $refresh = false)
    {
        if (headers_sent($file, $line)) throw new Exception("Unable to redirect to $url because the headers were already sent in $file:$line");
        
        $this->_currentStack['redirected'] = true;
        
        if ($refresh !== true)
        {
            if (self::$_thisRun) $this->_stack->saveTemp($this->_currentStack);
        }
        else
        {
            $this->setTempStack(NULL, true);
        }
        
        header("Location: $url");
        
        exit;
    }
    
    
    protected function _getRoute()
    {
        $this->clean('r', 'action', TYPE::_STR);
        
        if ($this->_stack->getTemp() !== NULL) return $this->_stack->getTemp();
        
        $_page = $this->pageArray($this->getUri(), false);
        
        $_route = array(
            // Request type protocol (NONSSL, SSL)
            'protocal' => $this->getProtocal(),
            
            // Unique process id
            'token' => uniqid(mt_rand(10000, 99999)),
            
            // GET params
            'params' => $this->clean($_SERVER['QUERY_STRING'], TYPE::_STR),
            
            // Requesting page:
            'page' => $this->pageString($_page),
            
            // Current request action: next, prev, submit, etc.
            'action' => $this->getRequest('action'),
            
            // Current module to execute
            'module' => $this->pageString($_page),
            
            // Status code (using the STATUSCODES constants)
            'status' => array(
                'code' => STATUSCODES::HTTP_OK,
                'message' => '',
            ),
            
            // Page redirected
            'redirected' => false,
            
            // Last process type
            'type' => self::ACTION_TYPE_PROCESSOR,
            
            // Messages: [Int offset] => Array ( 'message' => String 'Custom Message', 'parent' => String 'default', [ 'redirect' => Mixed False or url, [ 'status_code' => Int 0 ]] )
            'messages' => ($this->_previousStack['redirected'] AND !empty($this->_previousStack['messages'])) ? $this->_previousStack['messages'] : array(),
        );
        
        return $_route;
    }
    
    
    protected function _processMessages()
    {
        $_redirect = $_refresh = false;
        
        foreach ($this->_currentStack['messages'] AS $key => $message)
        {
            if ($message['status_code'] > 0)
            {
                $this->_currentStack['status']['code'] = $message['status_code'];
                
                $this->_currentStack['status']['message'] = $message['message'];
            }
            
            $this->_currentStack['messages'][$key]['status_code'] = 0;
            
            if ($message['redirect'] AND $_redirect === false)
            {
                if (is_string($message['redirect'])) $_refresh = true;
                
                $this->_currentStack['messages'][$key]['redirect'] = false;
                
                $_redirect = (is_string($message['redirect'])) ? $message['redirect'] : $this->getLinkBuilder()->url($this->_currentStack['page'], $this->_currentStack['params'], $this->_currentStack['protocal']);
            }
            
            $this->_currentStack['messages'][$key]['redirect'] = false;
        }
        
        if ($_redirect !== false) $this->_redirect($_redirect, $_refresh);
    }
    
    
    protected function _nextType()
    {
        $this->_currentStack['type']++;
    }
    
    
    protected function _navPlanner()
    {
        if ($this->_currentStack['type'] === self::ACTION_TYPE_PROCESSOR)
        {
            try
            {
                if ($this->_currentStack['action'] AND $this->_previousStack['token'] == $this->getSession('token'))
                {
                    $this->_processView();
                }
            }
            catch (Exception $e)
            {
                $this->errorView($e->getMessage(), STATUSCODES::HTTP_INTERNAL_SERVER_ERROR);
                
                return;
            }
            
            $this->_nextType();
            
            $this->_processMessages();
        }
        
        if ($this->_currentStack['type'] === self::ACTION_TYPE_SERVICE)
        {
            try
            {
                if ($this->_previousStack['token'] == $this->getSession('token'))
                {
                    $this->_serviceCall();
                }
            }
            catch (Exception $e)
            {
                $this->errorView($e->getMessage(), STATUSCODES::HTTP_INTERNAL_SERVER_ERROR);
                
                return;
            }
            
            $this->_nextType();
            
            $this->_processMessages();
        }
        
        if ($this->_currentStack['type'] >= self::ACTION_TYPE_PRESENTER)
        {
            try
            {
                if ($this->_presentView() !== true)
                {
                    if ($this->_previousStack['page'])
                    {
                        /** Get Lastpage **/
                        $_msgTemp = $this->_currentStack['messages'];
                        
                        $this->_currentStack = $this->_previousStack;
                        
                        $this->_currentStack['messages'] = $_msgTemp;
                        
                        $this->_currentStack['token'] = NULL;
                        
                        $_url = $this->getLinkBuilder()->url($this->_previousStack['page'], $this->_previousStack['params'], $this->_previousStack['protocal']);
                        
                        $this->_redirect($_url);
                    }
                    else if ($this->getSession('user')->getInfo('authorization/group_id') > 0)
                    {
                        /** Get Authenticated User's Home Page **/
                        $_url = $this->getLinkBuilder()->url('user/default/home', '', 'SSL');
                        
                        $this->_redirect($_url, true);
                    }
                    else
                    {
                        /** Get Public Home Page **/
                        $_url = $this->getLinkBuilder()->url('public/default/home');
                        
                        $this->_redirect($_url, true);
                    }
                }
            }
            catch (Exception $e)
            {
                $this->errorView($e->getMessage(), STATUSCODES::HTTP_INTERNAL_SERVER_ERROR);
                
                return;
            }
        }
    }
    
    
    protected function _serviceCall()
    {
        $_processErrors = array();
        
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        $_page = $_module['page'];
        
        if (App::classExists("Module_{$_view}_{$_path}_Service"))
        {
            try
            {
                $_processMessages = App::getInstance("Module_{$_view}_{$_path}_Service")->invoke()->getMessages();
            }
            catch (Exception $e)
            {
                if ($e->getCode() > EXCEPTIONCODES::OTHER)
                {
                    $this->errorView("Module_{$_view}_{$_path}_Service::service{$_page}() " . EXCEPTIONCODES::getMessageForCode($e->getCode()), STATUSCODES::HTTP_NOT_FOUND);
                }
                else
                {
                    throw new Exception($e->getMessage());
                }
            }
            
            $this->_currentStack['messages'] = array_merge($this->_currentStack['messages'], (array)$_processMessages);
        }
    }
    
    
    protected function _processView()
    {
        $_processErrors = array();
        
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        $_page = $_module['page'];
        
        if (App::classExists("Module_{$_view}_{$_path}_Processor"))
        {
            try
            {
                $_processMessages = App::getInstance("Module_{$_view}_{$_path}_Processor")->process()->getMessages();
            }
            catch (Exception $e)
            {
                if ($e->getCode() > EXCEPTIONCODES::OTHER)
                {
                    $this->errorView("Module_{$_view}_{$_path}_Processor::process{$_page}() " . EXCEPTIONCODES::getMessageForCode($e->getCode()), STATUSCODES::HTTP_NOT_FOUND);
                }
                else
                {
                    throw new Exception($e->getMessage());
                }
            }
            
            $this->_currentStack['messages'] = array_merge($this->_currentStack['messages'], (array)$_processMessages);
        }
    }
    
    
    protected function _presentView()
    {
        $_processErrors = array();
        
        static $_executed = false;
        
        $_module = $this->pageArray($this->_currentStack['module'], false);
        
        $_view = $_module['view'];
        
        $_path = $_module['path'];
        
        $_page = $_module['page'];
        
        if (App::classExists("Module_{$_view}_{$_path}_Presenter"))
        {
            try
            {
                $_processMessages = App::getInstance("Module_{$_view}_{$_path}_Presenter")->present()->getMessages();
                
                $_executed = true;
            }
            catch (Exception $e)
            {
                if ($e->getCode() > EXCEPTIONCODES::OTHER AND !$_executed)
                {
                    $_executed = true;
                    
                    $this->errorView("Module_{$_view}_{$_path}_Presenter::page{$_page}() " . EXCEPTIONCODES::getMessageForCode($e->getCode()), STATUSCODES::HTTP_NOT_FOUND);
                }
                else
                {
                    throw new Exception($e->getMessage());
                }
            }
            
            $this->_currentStack['messages'] = array_merge($this->_currentStack['messages'], (array)$_processMessages);
        }
        
        return $_executed;
    }
}

