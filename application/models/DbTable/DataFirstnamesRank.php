<?php

/**
 * AclRolesResourcesTable
 * 
 * @author sandyl
 * @version 
 */

class Application_Model_DbTable_DataFirstnamesRank extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'data_firstnames_rank';
	
	protected $_referenceMap = array(
			'DataFirstnames' => array(
					'columns' => array('data_firstnames_id'),
					'refTableClass' => 'Application_Model_DbTable_DataFirstnames',
					'refColumns' => array('id')
					)
	);

}
