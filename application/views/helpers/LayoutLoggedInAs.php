<?php

class My_View_Helper_LayoutLoggedInAs extends Zend_View_Helper_Abstract 
{
	private $_loginURL;
	private $_logoutURL;
	
    public function LayoutLoggedInAs()
    {
    	$naviContainer = $this->view->navigation()->getContainer();
    	$this->_loginURL = $naviContainer->findBy('id','2')->getHref();
    	$this->_logoutURL = $naviContainer->findBy('id','3')->getHref();
    	
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $username = $auth->getIdentity()->username;

            return 'Welcome ' . $username .  '. <a href="'.$this->_logoutURL.'">Logout</a>';
        } 

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if($controller == 'user' && $action == 'login') {
            return '<a href="'.$this->_loginURL.'">Login</a>';
        }

        return '<a href="'.$this->_loginURL.'">Login</a>';
    }
}