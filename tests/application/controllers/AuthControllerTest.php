<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AuthControllerTest
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class AuthControllerTest extends ControllerTestCase {
    
    public function testAccessToIndexIsRedirected() {
        $this->dispatch("/auth/");
        $this->assertRedirectTo('/auth/login');
    }
    
    public function testLoginView() {
        /*
        $auth = Zend_Auth::getInstance();

        // logged OK
        $this->assertTrue($auth->hasIdentity());
         * 
         */
    }
}
