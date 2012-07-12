<?php

/**
 * RolesTable
 * 
 * @author sandyl
 * @version 
 */

class Application_Model_DbTable_AclRoles extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'acl_roles';

	protected $_dependentTables = array('Application_Model_DbTable_Users');
	
}
