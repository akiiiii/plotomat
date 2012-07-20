<?php

class MyScaffoldingController extends Lex0r_Scaffolding {

    /**
     * @param type $options 
     */
    private function _setRoutingOption($options) {
        if (is_array($options['pagination']) && array_key_exists('routeName', $options['pagination'])) {
            $this->view->routeName = $options['pagination']['routeName'];
        } else {
            $this->view->routeName = 'default';
        }
    }
    
    /**
     *
     * @param type $dbSource
     * @param type $fields
     * @param type $options 
     * add route variable to internal variables on scaffold
     */
    protected function scaffold($dbSource, $fields = array(), $options = null) {
        $this->_setRoutingOption($options);
        parent::scaffold($dbSource, $fields, $options);
    }

    /**
     *
     * @param type $select
     * @param type $fields
     * @param type $options 
     * add route variable to internal variables on smartyQuery
     */
    protected function smartQuery($select, $fields = array(), $options = null) {
        $this->_setRoutingOption();
        parent::smartQuery($select, $fields, $options);
    }

}