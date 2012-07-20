<?php

require_once APPLICATION_PATH . '/controllers/MyScaffoldingController.php';

class NewsController extends MyScaffoldingController {

    public function init() {
        // $fields and $options are all optional
        // use if you want to use advanced features

        /*
        $options = array('pagination' => array('itemsPerPage' => 2));
        $fields = array('title' => array('title'    =>  'Titel',
                                        'searchable' => true,
                                        'sortable'  => true),
                        'body'  => array('title'    =>  'text',
                                        'searchable'=> true));
        */
        $options = array();
        $fields = array();

        $this->scaffold(new Application_Model_DbTable_News(), $fields, $options);
    }

}

?>