<?php

/*
 * ist als plugin in der bootstrap eingebunden => wird immer mit aufgerufen
 */

class Application_Plugin_Auth_AccessControl extends Zend_Controller_Plugin_Abstract {

    public function __construct(Zend_Auth $auth, Zend_Acl $acl) {
        /*
         * werden aus der bootstrap rein gereicht
         */
        $this->_auth = $auth;
        $this->_acl = $acl;
    }

    /*
     * wird vor Starten des Routers aufgerufen und prÃ¼ft, 
     * ob mit dem Aufruf Login-Daten Ãœbermittelt wurden
     * 
     * wird aufgerufen bevor Zend_Controller_Front den Router aufruft, 
     * um den Request anhand der registrierten Routen zu Ã¼berprÃ¼fen.
     * 
     * automatische ZEND FRAMEWORK Funktion
     */

    public function routeStartup(Zend_Controller_Request_Abstract $request) {
        //Zend_Auth::getInstance()->clearIdentity();
        //$this->_helper->redirector('index'); // back to login page

        if (!$this->_auth->hasIdentity()
                && $request->getPost('login_user') != null
                && $request->getPost('login_password') != null
                && $request->getPost('recaptcha_challenge_field') != null
                && $request->getPost('recaptcha_response_field') != null) {
            // POST-Daten bereinigen
            $filter = new Zend_Filter_StripTags();
            $username = $filter->filter($request->getPost('login_user'));
            $password = $filter->filter($request->getPost('login_password'));
            $captchaData = array(
                'recaptcha_challenge_field' => $filter->filter($request->getPost('recaptcha_challenge_field')),
                'recaptcha_response_field' => $filter->filter($request->getPost('recaptcha_response_field')));

            /// captcha
            $registry = Zend_Registry::getInstance();
            $pub = $registry->config->recaptcha->pubkey;
            $priv = $registry->config->recaptcha->privkey;
            $recaptcha_service = new Zend_Service_ReCaptcha($pub, $priv);
            $adapter = new Zend_Captcha_ReCaptcha();
            $adapter->setService($recaptcha_service);

            if (!$adapter->isValid($captchaData)) {
                $message = 'Captcha nochmal checken!';
                // Handle validation error
            } elseif (empty($username)) {
                $message = 'Bitte Benutzernamen angeben.';
            } elseif (empty($password)) {
                $message = 'Bitte Passwort angeben.';
            } else {

                $dbAdapter = Zend_Db_Table::getDefaultAdapter();
                $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
                /*
                 * standard-behandlung
                  $authAdapter->setTableName('users')
                  ->setIdentityColumn('username')
                  ->setCredentialColumn('pswd')
                  ->setCredentialTreatment('MD5(?)');
                 */
                /*
                 * extra join für den role-namen
                 */
                $authAdapter->setTableName(array('u' => 'users'))
                        ->setIdentityColumn('username')
                        ->setCredentialColumn('pswd')
                        ->setCredentialTreatment('MD5(?)')
                        ->getDbSelect()->joinLeft(array('ar' => 'acl_roles'), 'u.role_id = ar.id', array('role' => 'name', 'role_parent_id' => 'parent_id'))->group('u.id');
                $authAdapter->setIdentity($username);
                $authAdapter->setCredential($password);

                $result = $this->_auth->authenticate($authAdapter);
                if (!$result->isValid()) {

                    $messages = $result->getMessages();
                    $message = $messages[0];

                    /*
                      $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                      $flashMessenger->setNamespace('warning')->addMessage($messages[0]);

                      $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                      $redirector->gotoUrl('/login/index')->redirectAndExit();
                     */
                } else {
                    $storage = $this->_auth->getStorage();
                    // die gesamte Tabellenzeile in der Session speichern, wobei das Passwort unterdrückt wird
                    $storage->write($authAdapter->getResultRowObject(null, 'pswd'));

                    $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                    $flashMessenger->setNamespace('success')->addMessage('Thanx - you are logged in now!');

                    $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                    $redirector->gotoUrl('/index')->redirectAndExit();
                }

                $registry = Zend_Registry::getInstance();
                $view = $registry->view;
            }

            if (isset($message)) {
                $view->message = $message;
            }
        }
    }

    /*
     * automatically executed => assigns default role
     * 
     * preDispatch() wird von dem Dispatcher aufgerufen, 
     * bevor eine Aktion verarbeitet wird. 
     * Dieser Callback erlaubt ein Proxy oder Filter Verhalten. 
     * Durch VerÃ¤ndern des Requests und ZurÃ¼cksetzen des 
     * Verarbeitungsstatus (mittels Zend_Controller_Request_Abstract::setDispatched(false)) 
     * kann die aktuelle Aktion abgebrochen oder ersetzt werden. 
     * 
     * automatische ZEND FRAMEWORK Funktion
     */

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        /*
         * haben wir hier schon eine identitÃ¤t in der Session? Wenn nicht, dann guest
         */
        if ($this->_auth->hasIdentity() && is_object($this->_auth->getIdentity())) {
            $role = $this->_auth->getIdentity()->role;
        } else {
            $role = 'guest';
        }

        //Zend_Debug::dump($this->_auth->getIdentity());

        $resource = $request->getControllerName();
        $privilege = $request->getActionName();

        if (!$this->_acl->has($resource)) {
            /// was macht das hier genau? => nachschauen!!
            $resource = null;
        }

        // ACL to Zend_Navigation
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->navigation()->setAcl($this->_acl);
        $view->navigation()->setRole($role);

        if (!$this->_acl->isAllowed($role, $resource, $privilege)) {

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
                $redirector->gotoUrl('/user/login')->redirectAndExit();
            }
        }
    }

}