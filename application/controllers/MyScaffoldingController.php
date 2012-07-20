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

    /**
     *
     * @param Zend_Form $form 
     * override parent function -> module is not defined and therefore cannot be used when redirecting
     */
    protected function afterUpdate(Zend_Form $form) {
        $this->_redirect("{$this->view->controller}/index");
        return false;
    }
    
    protected function afterCreate(Zend_Form $form, $insertId) {
        if (isset($_POST[self::BUTTON_SAVE])) {
            $redirect = "{$this->view->controller}/index";
        } elseif (isset($_POST[self::BUTTON_SAVEEDIT])) {
            $redirect = "{$this->view->controller}/update/id/$insertId";
        } elseif (isset($_POST[self::BUTTON_SAVECREATE])) {
            $redirect = "{$this->view->controller}/create";
        }

        $this->_redirect($redirect);
        return false;
    }
    
    /**
     *
     * @throws Zend_Controller_Exception 
     * rewriting/copying complete deleteAction to remove controller in redirect links
     */
    public function deleteAction() {

        $params = $this->_getAllParams();
        $info = $this->getMetadata();

        if (count($info['primary']) == 0) {
            throw new Zend_Controller_Exception('The model you provided does not have a primary key, scaffolding is impossible!');
        }
        // Compound key support
        $primaryKey = array();
        foreach ($params AS $k => $v) {
            if (in_array($k, $info['primary'])) {
                $primaryKey["$k = ?"] = $v;
            }
        }

        try {
            $row = $this->dbSource->fetchAll($primaryKey);
            if ($row->count()) {
                $row = $row->current();
            } else {
                throw new Zend_Controller_Exception('Invalid request.');
            }

            $originalRow = clone $row;

            if ($this->beforeDelete($originalRow)) {
                $row->delete();
                $this->_helper->FlashMessenger($this->getActionMessage(self::ACTION_DELETE, self::MSG_OK));
                if ($this->afterDelete($originalRow)) {
                    $this->_redirect("{$this->view->controller}/index");
                }
            } else {
                $this->_helper->FlashMessenger($this->getActionMessage(self::ACTION_DELETE, self::MSG_ERR));
                $this->_redirect("{$this->view->controller}/index");
            }
        } catch (Zend_Db_Exception $e) {
            $this->lastError = $e->getMessage();
            $this->_helper->FlashMessenger($this->getActionMessage(self::ACTION_DELETE, self::MSG_OK));
            $this->_redirect("{$this->view->controller}/index");
        }
    }
    
}