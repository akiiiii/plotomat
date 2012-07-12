<?php

class My_View_Helper_LayoutGetHomeLink extends Zend_View_Helper_Abstract 
{
	private $_homeURL;
	
    public function LayoutGetHomeLink()
    {
    	$naviContainer = $this->view->navigation()->getContainer();
    	$this->_homeURL = $naviContainer->findBy('id','1')->getHref();
        return $this->_homeURL;
    }
}