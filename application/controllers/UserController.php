<?php

/**
 * 
 * @author sandyl
 *
 */
class UserController extends Zend_Controller_Action {
    /*
     * Entry into the user service layer
     *
     * @var Application_Model_UserService
     */

    protected $userService;

    /*
     * Entry into the user service layer
     *
     * @var Application_Model_AclService
     */
    protected $aclService;

    public function preDispatch() {
        $this->userService = new Application_Model_UserService();
        $this->aclService = new Application_Model_AclService();

        /*
         * @todo: cache, allow only roles for assigning which are below user-roles
         */
        $this->view->aclRoles = $this->aclService->GetAllAclRoles();
    }

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        if ($this->getRequest()->isPost()) {
            $this->userService->NewUser(
                    $this->_getParam('username'), $this->_getParam('email'), $this->_getParam('aclRole'));
        }

        //Zend_Debug::dump($this->userService->GetAllUsers());
        $rowset = $this->userService->GetAllUsers();
        $this->view->users = $rowset->toArray();
    }

    public function updateAction() {
        /*
         * @todo: validate input
         */
        $userID = $this->_getParam('id');

        if ($this->getRequest()->isPost()) {
            $this->userService->SaveUser(
                    $userID, $this->_getParam('username'), $this->_getParam('email'), $this->_getParam('aclRole'));
            $this->_redirect('user');
        } else {
            $this->view->user = $this->userService->GetUser($userID)->current();
        }
    }

    public function roleAction() {
        $name = $this->_getParam('name');
        $ctRow = $this->userService->GetAclRoleByName($name);
        if ($ctRow != null) {
            $this->view->aclRole = $ctRow;
        } else {
            /*
             * im preDispatch haben wir die schon mal rausgesucht - also gib mir einfach den ersten davon als default
             */
            $this->view->aclRole = $this->view->aclRoles->current();
        }
        $this->view->users = $this->view->aclRole->findDependentRowset('Application_Model_DbTable_Users');
    }

    /*
     * @todo: require delete confirmation
     */

    public function deleteAction() {
        $this->userService->DeleteUser($this->_getParam('id'));
        $this->_redirect('user');
    }

    public function logoutAction() {
        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
        $flashMessenger->setNamespace('success')->addMessage('Logged out! Bye bye ..');
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('index');
    }

    public function loginAction() {

        $form = new Application_Model_FormLogin(array('action' => '/user/login'));

        if ($this->getRequest()->isPost()) {
            $form_data = $this->getRequest()->getPost();

            if ($form->isValid($form_data)) {
                echo "Valid input!";
            } else {
                /*
                 * formatiere mir die errors so wie ich sie haben will
                 */
                $errorHelper = $this->view->getHelper('formErrors');
                $errorHelper->setElementStart('<div class="alert-message error"%s><p>');
                $errorHelper->setElementSeparator('</p><p>');
                $errorHelper->setElementEnd('</p></div>');

                $errorMsgs = $form->getMessages();
                if (isset($errorMsgs) && !empty($errorMsgs)) {
                    $this->view->errors = $errorMsgs;
                }
            }
            $email = $form->getValue('email');
            $pswd = $form->getValue('pswd');
        }

        $this->view->form = $form;
    }

    public function addAction() {
        $form = new Application_Model_FormLogin(array('action' => '/user/add'));
        $this->view->form = $form;
    }

}