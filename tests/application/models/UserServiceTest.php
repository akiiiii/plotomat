<?php

/**
 * Description of UserServiceTest
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class UserServiceTest extends ControllerTestCase {
    
    protected $users;
    protected $aclRoles;

    /* setting up the fixtures */
    public function setUp() {
        parent::setUp();
        //$this->users = new Application_Model_DbTable_Users();
        $this->users = $this->getMock('Application_Model_DbTable_Users', array(), array(), '', false);
        //$this->aclRoles = new Application_Model_DbTable_AclRoles();
        $this->aclRoles = $this->getMock('Application_Model_DbTable_AclRoles', array(), array(), '', false);
    }
    
    public function testUserCreation() {

    }
    
}
