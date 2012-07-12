<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initMyConfig()
	{
		$config = new Zend_Config($this->getOptions());
  		Zend_Registry::set('config', $config);
  		
		return $config; /// <= nicht sicher was das hier soll, war jedoch im BeispielCode vorhanden <= vielleicht erklärt's irgendwann mal irgendwas
	}
	
	/*
	protected function _initMongo() {
		
		/// kann man auch weglassen - der connected dann automatisch
		$master = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addMaster($master);
		
		//$slave = new Shanty_Mongo_Connection('mongodb://repo:hasiba3r@ds029287.mongolab.com:29287/stats_replica');
		//Shanty_Mongo::addSlave($slave);
	}
	*/

	protected function _initMyViewHelpers()
	{
		$this->bootstrap('layout'); // make sure layout is bootstrapped!
		
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		$view->doctype('XHTML1_STRICT');
		$view->setEncoding('UTF-8');
		$view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
		$view->headLink()->appendStylesheet('/styles/bootstrap.min.css');
		$view->headStyle()->appendStyle('body {
									        padding-top: 60px;
									      }
										.container-fluid > .content {
																	    margin-left: 0;
																	}');

		$view->headScript()->appendFile('js/jquery-1.7.1.min.js');
		$view->headScript()->appendFile('js/bootstrap-alerts.js');
		$view->headScript()->appendFile('js/bootstrap-dropdown.js');
		
		//// view helper path! 
		$view->addHelperPath(APPLICATION_PATH.'/views/helpers','My_View_Helper'); // Specify view helper and its path
	}
	
	protected function _initMyRouting()
	{
		$this->bootstrap('frontController'); // make sure frontcontroller is bootstrapped!
		
	    $front = Zend_Controller_Front::getInstance();
	    $router = $front->getRouter();

	    // Add default routes
	    $router->addRoute(
	    		'default',
	    		new Zend_Controller_Router_Route('/:controller/:action/:id', array(
	    				'controller' => 'index',
	    				'action' => 'index', 
	    				'id' => null,
	    		)));
	    
	    // Add pagination route
	    $router->addRoute(
	    		'pagination',
	    		new Zend_Controller_Router_Route('/:controller/:action/page/:page', array(
	    				'controller' => 'index',
	    				'action' => 'index',
	    				'page' => null,
	    		)));
	    
	    // Add some routes
	    $router->addRoute(
	    		'userId',
	    		new Zend_Controller_Router_Route('/user/:action/:id', array(
	    				'controller' => 'user',
	    				'id' => null
	    		)));
	    
	    $router->addRoute(
	    		'userName',
	    		new Zend_Controller_Router_Route('/user/role/:name', array(
	    				'controller' => 'user',
	    				'action' => 'role',
	    				'name' => null
	    		)));

	    $router->addRoute(
	    		'roleUpdate',
	    		new Zend_Controller_Router_Route('/role/:state/:id', array(
	    				'controller' => 'role',
	    				'action' => 'update',
	    				'state' => null, //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
	    				'id' => null //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
	    		)));

	    $router->addRoute(
	    		'resourceUpdate',
	    		new Zend_Controller_Router_Route('/resource/:state/:id', array(
	    				'controller' => 'resource',
	    				'action' => 'update',
	    				'state' => null, //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
	    				'id' => null //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
	    		)));
	    
	    // Returns the router resource to bootstrap resource registry
	    return $router;
	}
	

	/*
	 * http://stackoverflow.com/questions/7029307/no-default-adapters-in-zend-unless-i-add-them-explicitly-is-this-a-feature-or-a
	 */
	/*
	protected function _initDbUtf8()
	{
		$this->bootstrap('db'); // make sure db is bootstrapped already!
	
		//Zend_Debug::dump(Zend_Db_Table::getDefaultAdapter());
		
	    // force UTF-8 connection
	    $statement = new Zend_Db_Statement_Pdo(
	       Zend_Db_Table::getDefaultAdapter(),
	        "SET NAMES 'utf8'"
	    );
	    $statement->execute();
	}
	*/

	public function _initDbRegistry()
	{
		$this->bootstrap('multidb');
		$multidb = $this->getPluginResource('multidb');
		Zend_Registry::set('db_local', $multidb->getDb('local'));
	}
	

	protected function _initPluginAuth()
	{
		$this->bootstrap('multidb'); // make sure db is bootstrapped already!
		$this->bootstrap('frontController'); // make sure frontcontroller is bootstrapped!
		
		$auth = Zend_Auth::getInstance();
		
		//// hol mir mal die ACL-Daten vom Model - also aus der DB im besten Fall
		$acl = new Application_Model_Acl;
		
		//// zeig dem frontcontroller sein accesscontrol plugin
		$this->getResource('frontController')
				->registerPlugin(new Application_Plugin_Auth_AccessControl($auth, $acl))
				->setParam('auth', $auth);
		
		Zend_Registry::set('acl', serialize($acl));
	}

	
	protected function _initMyLayoutValues()
	{
		$this->bootstrap('layout'); // make sure layout is bootstrapped!
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		
		$registry = Zend_Registry::getInstance();
		$view->emailSupport = $registry->config->email->support;
	}


	/**
	* used for handling top-level navigation
	* @return Zend_Navigation
	*/
	protected function _initMyNavigation()
	{

		$this->bootstrap('layout'); // make sure layout is bootstrapped!
		
        $layout = $this->getResource('layout');
        $view = $layout->getView();  
	
        $home = new Zend_Navigation_Page_Mvc(array(
        		'label' 	=> 'Home',
        		'id' => 1,
        		'route' => 'default',
        		'module' => 'default',
        		'controller' => 'index',
        		'visible' => true, /// default => nicht sichtbar
        		'action'     => 'index'
        ));
        $login = new Zend_Navigation_Page_Mvc(array(
				'type' => 'Zend_Navigation_Page_MVC',
				'label'  => 'Login',
				'id' => 2,
				'route' => 'default',
				'module' => 'default',
				'controller' => 'user',
				'visible' => false, /// default => nicht sichtbar
				'action' => 'login',
		));
        $logout = new Zend_Navigation_Page_Mvc(array(
        		'type' => 'Zend_Navigation_Page_MVC',
        		'label'  => 'Logout',
        		'id' => 3,
        		'route' => 'default',
        		'module' => 'default',
        		'controller' => 'user',
        		'visible' => false, /// default => nicht sichtbar
        		'action' => 'logout',
        ));

        
		$container = new Zend_Navigation();
		$pages = array(
			
			$home,
			$login,
			$logout,

			array(	'type' => 'Zend_Navigation_Page_MVC',
					'label'  => 'User-Admin',
					'route' => 'default',
					'module' => 'default',
					'controller' => 'user',
					'action' => 'index',
					'resource' => 'user',
					'privilege' => 'index',
			),
			/*
			array(
					'type' => 'Zend_Navigation_Page_MVC',
					'label'  => 'Role-Admin',
					'route' => 'roleUpdate',
					'module' => 'default',
					'controller' => 'role',
					'action' => 'update',
					'resource' => 'role',
					'privilege' => 'update',
			),
			array(
					'type' => 'Zend_Navigation_Page_MVC',
					'label'  => 'Resource-Admin',
					'route' => 'resourceUpdate',
					'module' => 'default',
					'controller' => 'resource',
					'action' => 'update',
					'resource' => 'resource',
					'privilege' => 'update',
			),
			*/

			array(
					'type' => 'Zend_Navigation_Page_MVC',
					'label'  => 'News-Admin',
					'route' => 'default',
					'module' => 'default',
					'controller' => 'news',
					'action' => 'index',
					'resource' => 'news',
			),
			/*
			array(
					'type' => 'Zend_Navigation_Page_Uri',
					'label' => 'Administration', 
					'uri' => '#',
					'pages' =>	array(
							array(	'type' => 'Zend_Navigation_Page_MVC',
									'label'  => 'User-Admin',
									'route' => 'default',
									'module' => 'default',
									'controller' => 'user',
									'action' => 'index',
									'resource' => 'user',
									'privilege' => 'index',
							),
							array(
									'type' => 'Zend_Navigation_Page_MVC',
									'label'  => 'Role-Admin',
									'route' => 'roleUpdate',
									'module' => 'default',
									'controller' => 'role',
									'action' => 'update',
									'resource' => 'role',
									'privilege' => 'update',
							),
							array(
									'type' => 'Zend_Navigation_Page_MVC',
									'label'  => 'Resource-Admin',
									'route' => 'resourceUpdate',
									'module' => 'default',
									'controller' => 'resource',
									'action' => 'update',
									'resource' => 'resource',
									'privilege' => 'update',
							)),
			),
			*/

		    array(
		    	'type' => 'Zend_Navigation_Page_MVC',
		        'label' =>  'Scraper',
		    	'route' => 'default',
		    	'module' => 'default',
		    	'controller' => 'tools',
		        'action' => 'scrape',
		    ),
		
		    array(
		    	'type' => 'Zend_Navigation_Page_MVC',
		        'label' =>  'Contact',
		    	'route' => 'default',
		    	'module' => 'default',
		    	'controller' => 'contact',
		        'action' => 'index',
		    )
		);
		
		// add multiple pages
		$container->addPages($pages);
		$view->navigation($container);

		/*
		$acl = Zend_Acl::getInstance();
		$view->navigation()->setAcl($acl);
		*/

		$view->homeLink = $home->getHref();
		
	}  
	
}

