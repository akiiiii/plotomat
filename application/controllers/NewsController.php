<?php

require_once APPLICATION_PATH . '/controllers/ScaffoldingController.php';

class NewsController extends ScaffoldingController {

    public function init() {
        // $fields and $options are all optional
        // use if you want to use advanced features
        $options = array('pagination' => array('itemsPerPage' => 2),
            'customMessenger' => true);
        $fields = array();
        $this->scaffold(new Application_Model_DbTable_News(), $fields, $options);
    }

}

?>