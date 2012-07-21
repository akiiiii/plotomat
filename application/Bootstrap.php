<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initMyConfig() {
        $config = new Zend_Config($this->getOptions());
        Zend_Registry::set('config', $config);

        return $config; /// <= nicht sicher was das hier soll, war jedoch im BeispielCode vorhanden <= vielleicht erklärt's irgendwann mal irgendwas
    }

    protected function _initMongo() {

        /// kann man auch weglassen - der connected dann automatisch
        $master = new Shanty_Mongo_Connection('localhost');
        Shanty_Mongo::addMaster($master);
    }

    protected function _initMyViewHelpers() {
        $this->bootstrap('layout'); // make sure layout is bootstrapped!

        $layout = $this->getResource('layout');
        $view = $layout->getView();
        
        /* using view helpers to set meta data*/
        $view->doctype('XHTML1_STRICT');
        $view->setEncoding('UTF-8');
        
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headMeta()->setName('viewport', 'width=device-width, initial-scale=1.0');
        $view->headMeta()->setName('description', '');
        $view->headMeta()->setName('author', '');

                
        $view->headLink()->appendStylesheet('/ext/bootstrap/css/bootstrap.min.css');
        $view->headLink()->appendStylesheet('/css/bodystyle_addon.css');
        /* responsive has to be at bottom to overwrite padding-top */
        $view->headLink()->appendStylesheet('/ext/bootstrap/css/bootstrap-responsive.min.css');
        

        $view->inlineScript()->appendFile('/js/jquery-1.7.1.min.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-alert.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-dropdown.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-collapse.js');
        /*
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-transition.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-tab.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-tooltip.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-popover.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-button.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-carousel.js');
        $view->inlineScript()->appendFile('/ext/bootstrap/js/bootstrap-typeahead.js');
         */

        
        //// view helper path! 
        $view->addHelperPath(APPLICATION_PATH . '/views/helpers', 'My_View_Helper'); // Specify view helper and its path
    }

    protected function _initMyRouting() {
        $this->bootstrap('frontController'); // make sure frontcontroller is bootstrapped!

        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        
        // Add default routes
        $router->addRoute(
                'default', new Zend_Controller_Router_Route('/:controller/:action/:id', array(
                    'controller' => 'index',
                    'action' => 'index',
                    'id' => null,
                )));

        // Add pagination route
        $router->addRoute(
                'pagination', new Zend_Controller_Router_Route('/:controller/:action/page/:page', array(
                    'controller' => 'index',
                    'action' => 'index',
                    'page' => 1,
                )));
        
        // Add pagination orderby route
        $router->addRoute(
                'paginationOrderBy', new Zend_Controller_Router_Route('/:controller/:action/order/:orderby/:mode', array(
                    'controller' => 'index',
                    'action' => 'index',
                    'orderby' => null,
                    'mode' => null,
                )));

        // Add some routes
        $router->addRoute(
                'userId', new Zend_Controller_Router_Route('/user/:action/:id', array(
                    'controller' => 'user',
                    'id' => null
                )));

        $router->addRoute(
                'userName', new Zend_Controller_Router_Route('/user/role/:name', array(
                    'controller' => 'user',
                    'action' => 'role',
                    'name' => null
                )));

        $router->addRoute(
                'roleUpdate', new Zend_Controller_Router_Route('/role/:state/:id', array(
                    'controller' => 'role',
                    'action' => 'update',
                    'state' => null, //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
                    'id' => null //// default needed for navigation links to work=> $page->getHref() => funktioniert noch nicht auf unterSeiten
                )));

        $router->addRoute(
                'resourceUpdate', new Zend_Controller_Router_Route('/resource/:state/:id', array(
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

    /**
     * for possibility for using multible db-connections 
     */
    public function _initDbRegistry() {
        $this->bootstrap('multidb');
        $multidb = $this->getPluginResource('multidb');
        Zend_Registry::set('db_local', $multidb->getDb('local'));
    }

    /**
     * authentication via frontcontroller plugin 
     */
    protected function _initPluginAuth() {
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

    protected function _initMyLayoutValues() {
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
    protected function _initMyNavigation() {

        $this->bootstrap('layout'); // make sure layout is bootstrapped!
        $this->bootstrap('frontController'); // make sure frontcontroller is bootstrapped! => ACL

        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $home = new Zend_Navigation_Page_Mvc(array(
                    'label' => 'Home',
                    'id' => 1,
                    'route' => 'default',
                    'module' => 'default',
                    'controller' => 'index',
                    'visible' => true, /// default => nicht sichtbar
                    'action' => 'index'
                ));
        $login = new Zend_Navigation_Page_Mvc(array(
                    'type' => 'Zend_Navigation_Page_MVC',
                    'label' => 'Login',
                    'id' => 2,
                    'route' => 'default',
                    'module' => 'default',
                    'controller' => 'user',
                    'visible' => false, /// default => nicht sichtbar
                    'action' => 'login',
                ));
        $logout = new Zend_Navigation_Page_Mvc(array(
                    'type' => 'Zend_Navigation_Page_MVC',
                    'label' => 'Logout',
                    'id' => 3,
                    'route' => 'default',
                    'module' => 'default',
                    'controller' => 'user',
                    'visible' => false, /// default => not visible
                    'action' => 'logout',
                ));
       
        /* create navigation container */
        $container = new Zend_Navigation();
        $pages = array(
            $home,
            $login,
            $logout,

            array(
                'type' => 'Zend_Navigation_Page_MVC',
                'label' => 'News-Admin',
                'route' => 'default',
                'module' => 'default',
                'controller' => 'news',
                'action' => 'index',
                'resource' => 'news',
            ),
            array(
                'type' => 'Zend_Navigation_Page_Uri',
                'label' => 'Tools',
                'uri' => '#',
                'pages' => array(
                    array('type' => 'Zend_Navigation_Page_MVC',
                        'label' => 'Scanner',
                        'route' => 'default',
                        'module' => 'default',
                        'controller' => 'tools',
                        'action' => 'scan',
                )),
            ),
            array(
                'type' => 'Zend_Navigation_Page_Uri',
                'label' => 'Administration',
                'resource' => 'administration',
                'uri' => '#',
                'pages' => array(
                    array('type' => 'Zend_Navigation_Page_MVC',
                        'label' => 'User-Admin',
                        'route' => 'default',
                        'module' => 'default',
                        'controller' => 'user',
                        'action' => 'index',
                        'resource' => 'user',
                        'privilege' => 'index',
                    ),
                    array(
                        'type' => 'Zend_Navigation_Page_MVC',
                        'label' => 'Role-Admin',
                        'route' => 'roleUpdate',
                        'module' => 'default',
                        'controller' => 'role',
                        'action' => 'update',
                        'resource' => 'role',
                        'privilege' => 'update',
                    ),
                    array(
                        'type' => 'Zend_Navigation_Page_MVC',
                        'label' => 'Resource-Admin',
                        'route' => 'resourceUpdate',
                        'module' => 'default',
                        'controller' => 'resource',
                        'action' => 'update',
                        'resource' => 'resource',
                        'privilege' => 'update',
                )),
            ),
            array(
                'type' => 'Zend_Navigation_Page_MVC',
                'label' => 'Contact',
                'route' => 'default',
                'module' => 'default',
                'controller' => 'contact',
                'action' => 'index',
            )
        );
       
        // add multiple pages
        $container->addPages($pages);
        $view->navigation($container);
        
        $view->homeLink = $home->getHref();
    }

}

