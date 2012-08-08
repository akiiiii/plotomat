<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Stats
 *
 * @author Sandy Lewanscheck <sandy@lewanscheck.de>
 */
class Application_Model_Stats {
    
    protected $countryList = array();

    public function getCountries() {
        return array_keys($this->countryList);
    }
    
    public function addCountry($country = null) {
        
        if($country == null)
            $this->_clearList();
        
        if(array_key_exists($country, $this->countryList))
           $this->countryList[$country]++; 
        else
            $this->countryList[$country] = 1;        
    }
    
    private function _clearList() {
        $this->countryList = array();
    }
}

