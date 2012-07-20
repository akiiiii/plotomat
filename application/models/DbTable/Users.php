<?php

/**
 * UsersTable
 * 
 * @author sandyl
 * @version 
 */
class Application_Model_DbTable_Users extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'users';
    protected $_referenceMap = array(
        'AclRoles' => array(
            'columns' => array('role_id'),
            'refTableClass' => 'Application_Model_DbTable_AclRoles',
            'refColumns' => array('id')
        )
    );
    
    protected $_dependentTables = array('Users');

}
