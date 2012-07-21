<?php

require_once APPLICATION_PATH . '/controllers/MyScaffoldingController.php';

class NewsController extends MyScaffoldingController {

    public function init() {
        // $fields and $options are all optional
        // use if you want to use advanced features

        $options = array('pagination' => array('itemsPerPage' => 3,
            'routeName' => 'pagination'),
            'viewFolder' => 'scaffolding',
            'entityTitle' => 'entry',
            'customMessenger' => true);

        $fields = array('id' => array('title' => 'id',
                'search' => true,
                'sort' => array('default' => 'asc')
            ),
            'title' => array('title' => 'Titel',
                'search' => true,
                'sort' => true),
            'body' => array('title' => 'text',
                'search' => true));

        $this->scaffold(new Application_Model_DbTable_News(), $fields, $options);
    }

}

?>