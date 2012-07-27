<?php

class Application_Model_UserService {
    /*
     * users Zend Table
     * @var UsersTable
     */

    protected $users;

    /*
     * acl_roles Zend Table
     * @var AclRolesTable
     */
    protected $aclRoles;

    function __construct(Application_Model_DbTable_Users $usersTableModel, Application_Model_DbTable_AclRoles $aclRolesTableModel) {
        $this->users = $usersTableModel;
        $this->aclRoles = $aclRolesTableModel;
    }

    private function getWhereClauseForUserId($id) {
        return $this->users->getAdapter()->quoteInto('id = ?', $id);
    }

    public function SaveUser($id, $username, $email, $role_id) {
        if ($role_id == '')
            $role_id = NULL;
        $params = array(
            'username' => $username,
            'email' => $email,
            'role_id' => $role_id);
        $this->users->update($params, $this->getWhereClauseForUserId($id));
    }

    public function GetAclRoleByName($name) {
        $where = $this->aclRoles->getAdapter()->quoteInto('LOWER(name) = ?', strtolower($name));
        return $this->aclRoles->fetchRow($where);
    }

    public function DeleteUser($id) {
        $this->users->delete($this->getWhereClauseForUserId($id));
    }

    public function NewUser($username, $email, $role_id) {
        if ($role_id == '')
            $role_id = NULL;
        $params = array(
            'username' => $username,
            'email' => $email,
            'role_id' => $role_id);
        $this->users->insert($params);
    }

    public function GetAllUsers() {
        $select = $this->users->select();
        $select->order('username');

        return $this->users->fetchAll($select);
    }

    public function GetUser($id) {
        $row = $this->users->find($id);
        return $row;
    }

}

?>