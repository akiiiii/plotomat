<?php

/**
 * Description of StatsTest
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class Model_StatsTest extends ControllerTestCase {
    
    protected $stats;

    public function setUp() {
        parent::setUp();
        $this->stats = new Application_Model_Stats();
    }
    
    public function testCanDoUnitTest() {
        $this->assertTrue(true);
    }
    
    public function testCanAddCountry() {
        $testCountry = "Canada";
        $this->assertEquals(0, count($this->stats->getCountries()));
        
        $this->stats->addCountry($testCountry);
        
        foreach($this->stats->getCountries() as $country) {
            if($country == $testCountry) {
                $this->assertEquals($country, $testCountry);
            }
        }
        $this->assertEquals(1, count($this->stats->getCountries()));
    }
}
