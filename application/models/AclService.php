<?php

class Application_Model_AclService {
    /*
     * acl_resources Zend Table
     * @var aclResourcesTable
     */

    protected $aclResources;

    /*
     * acl_roles Zend Table
     * @var AclRolesTable
     */
    protected $aclRoles;

    /*
     * acl_roles_resources Zend Table
     * @var AclRolesResourcesTable
     */
    protected $aclRolesResources;

    function __construct() {
        $this->aclResources = new Application_Model_DbTable_AclResources();
        $this->aclRoles = new Application_Model_DbTable_AclRoles();
        $this->aclRolesResources = new Application_Model_DbTable_AclRolesResources();
    }

    function GetRoleById($id) {
        $row = $this->aclRoles->find($id);
        return $row;
    }

    public function GetAllAclRoles() {
        $select = $this->aclRoles->select();
        $select->order('name');

        return $this->aclRoles->fetchAll($select);
    }

    function GetResourceById($id) {
        $row = $this->aclResources->find($id);
        return $row;
    }

    public function GetAllAclResources() {
        $select = $this->aclResources->select();
        $select->order('name');

        return $this->aclResources->fetchAll($select);
    }

}

?>