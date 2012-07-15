<?php

/**
 * AclRolesResourcesTable
 * 
 * @author sandyl
 * @version 
 */
class Application_Model_DbTable_AclRolesResources extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'acl_roles_resources';
    protected $_referenceMap = array(
        'AclRoles' => array(
            'columns' => array('acl_role_id'),
            'refTableClass' => 'Application_Model_DbTable_AclRoles',
            'refColumns' => array('id')
        ),
        'AclResources' => array(
            'columns' => array('acl_resource_id'),
            'refTableClass' => 'Application_Model_DbTable_AclResources',
            'refColumns' => array('id')
            ));

}
