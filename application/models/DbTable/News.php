<?php

/**
 * NewsTable
 * 
 * @author sandyl
 * @version 
 */

class Application_Model_DbTable_News extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'news';

	protected $_referenceMap = array(
			'Users' => array(
					'columns' => array('user_id'),
					'refTableClass' => 'Application_Model_DbTable_Users',
					'refColumns' => array('id')
			)
	);
	
}
