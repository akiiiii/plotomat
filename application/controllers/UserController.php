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
        $this->userService = new Application_Model_UserService(new Application_Model_DbTable_Users(), new Application_Model_DbTable_AclRoles());
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

    public function addAction() {
        $form = new Application_Model_Form_Login(array('action' => '/user/add'));
        $this->view->form = $form;
    }

}