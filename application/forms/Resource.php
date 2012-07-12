<?php

class Application_Form_Resource extends Zend_Form 
{
	protected $formState; /// create-mode or update-mode?
	protected $currentResource;
	protected $aclService;
	
	/*
	 * $currentRole is rowSet of Role-Table
	 */
	public function __construct($formState="create",$currentResource = null)
	{
		/// initialize aclService
		$this->aclService = new Application_Model_AclService();
		$this->formState = $formState;
		$this->currentResource = $currentResource;
		
		$id = new Zend_Form_Element_Hidden('id');
		
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel("Resource name:");
			
		$this->addElements(array($id,$name));
		$this->addSubmit();
		
		// create or update depending on the current state
		if($formState == 'update')
		{
			// set variables
			$rowArr = $currentResource[0]->toArray(); // in array umwandeln
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
		
		$resourcesTable = new Application_Model_DbTable_AclResources();
		
		if($this->formState == 'update') {
			foreach($this->getElements() as $element) {
				$value = $element->getValue();
				$this->currentResource[0][$element->getId()] = $value;
			}
			$this->currentResource[0]->save();
		} else {
			$resourcesTable->insert($this->getValues());
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