<?php
class Application_Model_FormLogin extends Zend_Form
{

	public function __construct($options = null)
	{
		parent::__construct($options);
	
		$this->setName('login');
		$this->setMethod('post');
		$this->setAction($options['action']);
		
		$user = new Zend_Form_Element_Text('login_user');
		$user->setAttrib('size', 35)
			  ->removeDecorator('label')
			  ->removeDecorator('htmlTag')
			  ->setRequired(true);
	
		$pswd = new Zend_Form_Element_Password('login_password');
		$pswd->setAttrib('size', 35)
			 ->removeDecorator('label')
			 ->removeDecorator('htmlTag')
			 ->setRequired(true);
		
		$pswd = new Zend_Form_Element_Password('login_password');
		$pswd->setAttrib('size', 35)
		->removeDecorator('label')
		->removeDecorator('htmlTag')
		->setRequired(true);

		//// ReCaptcha
		$registry = Zend_Registry::getInstance();
        $pub = $registry->config->recaptcha->pubkey;
		$priv = $registry->config->recaptcha->privkey;

        $recaptcha = new Zend_Service_ReCaptcha($pub, $priv,
                NULL, array('theme' => 'clean'));

        $captcha = new Zend_Form_Element_Captcha('login_captcha',
            array( 'captcha' =>  'ReCaptcha',
                	'captchaOptions'        => array(
                    'captcha'   => 'ReCaptcha',
                    'service' => $recaptcha,
                	'messages' => array(
                		'badCaptcha' => 'You have entered an invalid value for the captcha.'
                	)
                )
            )
        );
        $captcha->removeDecorator('label')
        ->removeDecorator('htmlTag')
        ->setRequired(true);
	
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Login')->removeDecorator('DtDdWrapper');
		
		$this->setDecorators( 
			array (
				array('ViewScript', 
					array('viewScript' => '_form_login.phtml'))));
				
		$this->addElements(array($user, $pswd, $captcha, $submit));
		
	}	
}