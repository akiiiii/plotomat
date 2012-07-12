<?php
class Application_Model_FormRegister extends Zend_Form
{

	public function __construct($options = null)
	{
		parent::__construct($options);
	
		$this->setName('login');
		$this->setMethod('post');
		$this->setAction($options['action']);
		
		$email = new Zend_Form_Element_Text('email');
		$email->setAttrib('size', 35)
			  ->removeDecorator('label')
			  ->removeDecorator('htmlTag')
			  ->addValidator('emailAddress')
			  ->setRequired(true);
	
		$pswd = new Zend_Form_Element_Password('pswd');
		$pswd->setAttrib('size', 35)
			 ->removeDecorator('label')
			 ->removeDecorator('htmlTag')
			 ->setRequired(true);
	
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Login')->removeDecorator('DtDdWrapper');

		$this->setDecorators( 
			array (
				array('ViewScript', 
					array('viewScript' => '_form_login.phtml'))));
				
		$this->addElements(array($email, $pswd, $submit));
		
	}	
}