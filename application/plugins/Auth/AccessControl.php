<?php

/*
 * ist als plugin in der bootstrap eingebunden => wird immer mit aufgerufen
 */

class Application_Plugin_Auth_AccessControl extends Zend_Controller_Plugin_Abstract {

    private $_auth = null;
    private $_acl = null;


    public function __construct(Zend_Auth $auth, Zend_Acl $acl) {
        /*
         * werden aus der bootstrap rein gereicht
         */
        $this->_auth = $auth;
        $this->_acl = $acl;
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        /*
         * haben wir hier schon eine identität in der Session? Wenn nicht, dann guest
         */
        if ($this->_auth->hasIdentity() && is_object($this->_auth->getIdentity())) {
            $role = $this->_auth->getIdentity()->role;
        } else {
            $role = 'guest';
        }

        //Zend_Debug::dump($this->_auth->getIdentity());

        $resource = $request->getControllerName();
        $action = $request->getActionName();

        
        // ACL to Zend_Navigation
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->navigation()->setAcl($this->_acl);
        $view->navigation()->setRole($role);

        if (!$this->_acl->isAllowed($role, $resource, $action)) {

            //// der ist eingeloggt - hat also schon Rechte bzw. auch nicht
            if ($this->_auth->hasIdentity()) {

                //keine Rechte für diese Seite - zurück zur Index!
                /*
                 * Umleitung => ABER: index/index muss auch eine erlaubte seite sein ;)
                 */
                $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                $flashMessenger->setNamespace('error')->addMessage('You do not have the right to access this page.');

                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoUrl('/index/index')->redirectAndExit();
            } else {
                //nicht angemeldet -> Login
                /*
                 * Umleitung => ABER: user/login muss auch eine erlaubte seite sein ;)
                 */
                $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                $flashMessenger->setNamespace('error')->addMessage('Please log in first!');

                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoUrl('/auth/login')->redirectAndExit();
            }
        }
    }

}