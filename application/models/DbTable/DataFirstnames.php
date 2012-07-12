<?php

/**
 * UsersTable
 * 
 * @author sandyl
 * @version 
 */

class Application_Model_DbTable_DataFirstnames extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'data_firstnames';
        
        protected $_dependentTables = array('Application_Model_DbTable_DataFirstnamesRank');

}
