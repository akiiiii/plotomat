<?php

class Application_Form_Role extends Zend_Form 
{
	protected $formState; /// create-mode or update-mode?
	protected $currentRole;
	protected $aclService;
	
	/*
	 * $currentRole is rowSet of Role-Table
	 */
	public function __construct($formState="create",$currentRole = null)
	{
		/// initialize aclService
		$this->aclService = new Application_Model_AclService();
		$this->formState = $formState;
		$this->currentRole = $currentRole;
		
		$id = new Zend_Form_Element_Hidden('id');
		
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel("Role name:");
		
		$parent_id = new Zend_Form_Element_Select('parent_id');
		$parent_id->addMultiOption(NULL, '');
		$roleValues = $this->aclService->GetAllAclRoles();
		foreach ($roleValues as $valueSet) {
			$parent_id->addMultiOption($valueSet->id, $valueSet->name);
		}
		$parent_id->setLabel("Role parent:");
			
		$this->addElements(array($id,$name,$parent_id));
		$this->addSubmit();
		
		// create or update depending on the current state
		if($formState == 'update')
		{
			// set variables
			$rowArr = $currentRole[0]->toArray(); // in array umwandeln
			
			Zend_Debug::dump($rowArr);			
			foreach($rowArr as $key=>$value)
			{
				$this->getElement($key)->setValue($value);
			}
		}
		
		$this->setDecorators(array(
				'FormElements',
				array('HtmlTag', array('tag' => 'dl')),
				'Form'
		));
	}
	
	public function persistData()
	{
		$this->removeSubmit(); /// remove for insert
		
		$rolesTable = new Application_Model_DbTable_AclRoles();
		
		if($this->formState == 'update') {
			foreach($this->getElements() as $element) {
				$value = $element->getValue();
				if($value == '')
					$value = null;
				$this->currentRole[0][$element->getId()] = $value;
			}
			$this->currentRole[0]->save();
		} else {
			$rolesTable->insert($this->getValues());
		}
		
		$this->addSubmit();
	}
	
	private function addSubmit() {
		$this->addElement(new Zend_Form_Element_Submit($this->formState));
	}
	
	private function removeSubmit() {
		$this->removeElement($this->formState);
	}
}

?>