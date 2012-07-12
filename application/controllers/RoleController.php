<?php

/**
 * 
 * @author sandyl
 *
 */

class RoleController extends Zend_Controller_Action
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
		
		if($state == "update")
		{
			// preset variables
			$this->view->form = new Application_Form_Role($state, $this->aclService->GetRoleById($id));
		}
		else
		{
			$this->view->form = new Application_Form_Role();
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