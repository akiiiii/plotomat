<?php

/**
 * Description of indexControllerTest
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class IndexControllerTest extends ControllerTestCase {
    
    public function testCanGetToIndexPage() {
        $this->dispatch("/");
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertResponseCode('200');
    }
 
}

?>
