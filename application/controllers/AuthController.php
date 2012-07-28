<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class AuthController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        /* Display only view */
    }

    public function loginAction() {
        
        // already logged in?
        if(Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirect('index');
        }
        
        $form = new Application_Model_Form_Login(array('action' => '/auth/login'));
        
        $request = $this->getRequest();
        /* was the form submitted? */
        if($request->isPost()) {
            $form_data = $this->getRequest()->getPost();

            if ($form->isValid($form_data)) {
                $authAdapter = $this->_getAuthAdapter();
                
                $username = $form->getValue('login_user');
                $password = $form->getValue('login_password');
            
                $authAdapter->setIdentity($username)
                        ->setCredential($password);

                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);

                if($result->isValid()) {
                    $identity = $authAdapter->getResultRowObject();

                    $authStorage = $auth->getStorage();
                    $authStorage->write($identity);
                    
                    $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                    $flashMessenger->setNamespace('success')->addMessage('Yeah! Logged in. :)');
                    
                    $this->_redirect('index');           
                } else {
                    $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                    $flashMessenger->setNamespace('error')->addMessage('Username or password wrong. Sorry.');
                }
            }
        }
        $this->view->form = $form;
    }
    
    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
        $flashMessenger->setNamespace('info')->addMessage('You are now logged out. Bye, bye!');
        $this->_redirect('index');
    }
    
    private function _getAuthAdapter() {
        $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
        $authAdapter->setTableName(array('u' => 'users'))
                        ->setIdentityColumn('username')
                        ->setCredentialColumn('pswd')
                        ->setCredentialTreatment('MD5(?)')
                        ->getDbSelect()->joinLeft(array('ar' => 'acl_roles'), 'u.role_id = ar.id', array('role' => 'name', 'role_parent_id' => 'parent_id'))->group('u.id');
        return $authAdapter;
    }
}