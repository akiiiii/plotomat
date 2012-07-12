<?php

/**
 * 
 * @author sandyl
 *
 */

class ResourceController extends Zend_Controller_Action
{

	/*
	 * Entry into the role service layer
	*
	* @var Application_Model_AclService
	*/
	protected $aclService;
	
	public function preDispatch()
	{
		$this->aclService = new Application_Model_AclService();
	}
	
	public function init()
	{
		/* Initialize action controller here */
	}
	
	public function updateAction()
	{
		$state = $this->_getParam('state');
		$id = $this->_getParam('id');
		
		//Zend_Debug::dump($this->_getAllParams());
		if($state == "update")
		{
			// preset variables
			$this->view->form = new Application_Form_Resource($state, $this->aclService->GetResourceById($id));
		}
		else
		{
			$this->view->form = new Application_Form_Resource();
		}
				
		if($this->getRequest()->isPost() && 
				$this->view->form->isValid($this->getRequest()->getParams())
		)
		{
			$this->view->form->persistData();
		}
		
	}
	
}

?>