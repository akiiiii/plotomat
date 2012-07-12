<?php

class Application_Model_Acl extends Zend_Acl
{

	public function __construct()
	{
		
		// erstmal - Anfang
		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
		$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
		
		// RESSOURCES
		$this->add(new Zend_Acl_Resource('user'));
		$this->add(new Zend_Acl_Resource('contact'));
		$this->add(new Zend_Acl_Resource('tools'));
		$this->add(new Zend_Acl_Resource('index'));
		$this->add(new Zend_Acl_Resource('role'));
		$this->add(new Zend_Acl_Resource('resource'));
		$this->add(new Zend_Acl_Resource('rest'));
		$this->add(new Zend_Acl_Resource('news'));
		
		
		$this->addRole(new Zend_Acl_Role('guest'));
		$this->addRole(new Zend_Acl_Role('standard'),'guest');
		$this->addRole(new Zend_Acl_Role('admin'), 'standard');
		$this->addRole(new Zend_Acl_Role('superadmin'), 'admin');
		
		//// alles erlauben für superadmin
		$this->allow('superadmin');

		$this->allow('guest','user','login');
		$this->allow('guest','contact','index');
		$this->allow('guest','tools','scrape');
		$this->allow('guest','index','index');
		
		//$this->deny('superadmin', 'role');
		/*		
		$this->deny('guest', 'redaktion');
		
		$this->allow('admin', 'admin');
		*/
	}
}