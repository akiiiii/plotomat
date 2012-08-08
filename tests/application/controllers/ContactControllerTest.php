<?php

/**
 * Description of ContactControllerTest
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class ContactControllerTest extends ControllerTestCase {
    
    public function testCanGetToContactPage() {
        $this->dispatch("/contact");
        $this->assertController('contact');
        $this->assertAction('index');
        $this->assertResponseCode('200');
        $this->assertXpath('/html/body/div[2]/address', 'no address defined on Contact page!');
    }
    
}

