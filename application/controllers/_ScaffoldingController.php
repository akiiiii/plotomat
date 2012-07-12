<?php
/*
Copyright (c) 2010, Alex Oroshchuk
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

    * Neither the name of Zend Technologies USA, Inc. nor the names of its
      contributors may be used to endorse or promote products derived from this
      software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * This Zend controller extension class allows you to quickly scaffold
 * and admin interface for an application, using Zend MVC core components.
 * The controllers you would like to scaffold must extend this one, and you will
 * automatically have create, update, delete and list actions.
 *
 * This script was originally inspired by Benjamin Eberlei's scaffolding class
 * @link http://www.whitewashing.de/blog/articles/34/
 *
 * @author Alex Oroshchuk (oroshchuk@gmail.com)
 * @copyright 2010 Alex Oroshchuk
 * @version 0.5 Beta
 */

class _ScaffoldingController extends Zend_Controller_Action
{

    const ACTION_INDEX  = 'index';
    const ACTION_LIST   = 'list';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ID_TOKEN      = 'zs';

    /**
     * Default scaffolding options.
     * @var Array
     */
    private $_scaffOptions = array(
        'pkEditable'        => false,
        'viewFolder'        => 'scaffolding',
        'entityTitle'       => 'entity',
        'readonly'          => false,
        'disabledActions'   => array()
    );

    /**
     * Scaffolding field definitions.
     * @var Array
     */
    private $_scaffFields;

    /**
     * Data providing class.
     * @var Zend_Db_Table_Abstract|Zend_Db_Table_Select|Zend_Db_Select
     */
    private $_scaffDb;

    /**
     * Cached table metadata.
     * @var Array
     */
    private $_scaffMeta;

    /**
     * General controller initialization.
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Initializes scaffolding.
     *
     * @param Zend_Db_Table_Abstract|Zend_Db_Select $dataProvider respective model instance
     * @param array $fields field definitions
     * @param Zend_Config|Array $options
     */
    protected function initScaffolding($dataProvider, $fields = array(), $options = null)
    {
        if (!($dataProvider instanceof Zend_Db_Table_Abstract
                || $dataProvider instanceof Zend_Db_Select || $dataProvider instanceof Zend_Db_Table_Select))
            throw new Zend_Controller_Exception(
                    'Scaffolding initialization requires'
                    . ' an instance of Zend_Db_Table_Abstract,'
                    . ' Zend_Db_Table_Select or Zend_Db_Select');

        // If readonly restrict all other actions except for index and list
        // @todo: reverse check - enable readonly if all actions disabled
        if (isset($options['readonly']))
            $this->_scaffOptions['disabledActions'] =
                array(self::ACTION_CREATE, self::ACTION_DELETE, self::ACTION_UPDATE);

        $this->_scaffDb     = $dataProvider;
        $this->_scaffFields = $fields;
        if (is_array($options))
            $this->_scaffOptions = array_merge($this->_scaffOptions, $options);

        // Do not override view script path if the action requested is not
        // one of the standard scaffolding actions
        $scaffActions = array(self::ACTION_LIST, self::ACTION_INDEX,
                              self::ACTION_CREATE, self::ACTION_UPDATE,
                              self::ACTION_DELETE);
        
        $action = $this->getRequest()->getActionName();
        if (in_array($action, $scaffActions)) {
            $this->getHelper('ViewRenderer')->setViewScriptPathSpec(sprintf('%s/:action.:suffix', $this->_scaffOptions['viewFolder']));
        }

        $this->view->module     = $this->getRequest()->getModuleName();
        $this->view->controller = $this->getRequest()->getControllerName();
        
        // wird zentral gemacht => mit wichtigkeitsmapping
        //$this->view->messages   = $this->_helper->getHelper('FlashMessenger')->getMessages();
    }

    /**
     * Entity update handler.
     */
    public function updateAction()
    {
        if (in_array(self::ACTION_UPDATE, $this->_scaffOptions['disabledActions']))
            throw new Zend_Controller_Exception('This action is disabled.');

        $info = $this->_getMetadata();

        if (count($info['primary']) > 1) {
            throw new Zend_Controller_Exception('Zend_Controller_Scaffolding does not support compound keys.');
        } elseif (count($info['primary']) == 0) {
            throw new Zend_Controller_Exception('The model you provided does not have a primary key, scaffolding is impossible.');
        } else {
            $primaryKey = null;
            $params = $this->_getAllParams();
            // @todo: compound key support
            foreach($params AS $k => $v) {
                if(in_array($k, $info['primary'])) {
                    $primaryKey = $v;
                    break;
                }
            }

            /*
            $select = $this->_scaffDb->select();
            $select->order('id');
            Zend_Debug::dump($this->_scaffDb->fetchAll($select));
            */
            
            $entity = $this->_scaffDb->find($primaryKey)->current()->toArray();
            $form = $this->_initEditForm($entity);
            $populate = true;

            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $populate = false;
                $formValues = $form->getValues();
                $pkValue = $formValues[array_shift($info['primary'])];

                list($values, $where, $relData) = $this->_getDbValuesUpdate($formValues);

                // Save common submitted fields
                if (!is_null($values) && !is_null($where)) {

                    $this->_scaffDb->update($values, $where);

                    // Save many-to-many field to the corresponding table
                    if (count($relData)) {
                        foreach ($relData as $m2mData) {
                            $m2mTable   = $m2mData[0];
                            $m2mValues  = is_array($m2mData[1]) ? $m2mData[1] : array();

                            $m2mInfo    = $m2mTable->info();
                            $tableClass = get_class($this->_scaffDb);
                            foreach ($m2mInfo['referenceMap'] as $rule => $ruleDetails) {
                                if ($ruleDetails['refTableClass'] == $tableClass) {
                                    $selfRef = $ruleDetails['columns'];
                                } else {
                                    $relatedRef = $ruleDetails['columns'];
                                }
                            }

                            $m2mTable->delete("$selfRef = $pkValue");

                            foreach ($m2mValues as $v)
                                $m2mTable->insert(array($selfRef => $pkValue, $relatedRef => $v));
                        }
                    }

                    //$this->_helper->FlashMessenger("The selected {$this->_scaffOptions['entityTitle']} was updated.");
                    $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                    $flashMessenger->setNamespace('success')->addMessage("The selected {$this->_scaffOptions['entityTitle']} was updated.");
                    
                    //$this->_redirect($this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/index');
                    $moduleName = $this->getRequest()->getModuleName();
                    if($moduleName != 'default') {
                    	$module = $this->getRequest()->getModuleName() . '/' ;
                    }
                    $this->_redirect($module. $this->getRequest()->getControllerName() . '/index');
                }
            }

            if ($populate === true) {
                // Load common field values
                foreach ($entity as $field => $value)
                    // Apply field modifier if any
                    if (isset($this->_scaffFields[$field]['loadModifier'])) {
                        if (function_exists($this->_scaffFields[$field]['loadModifier']))
                            $entity[$field] = call_user_func($this->_scaffFields[$field]['loadModifier'], $value);
                        else
                            $entity[$field] = $this->_scaffFields[$field]['loadModifier'];
                    }

                // Load many-to-many field values
                foreach ($this->_scaffFields as $field => $fieldDetails) {
                    if (isset($fieldDetails['dependentTable'])) {
                        $m2mTable = $fieldDetails['dependentTable'];
                        $m2mInfo = $m2mTable->info();

                        $tableClass = get_class($this->_scaffDb);
                        foreach ($m2mInfo['referenceMap'] as $rule => $ruleDetails) {
                            if ($ruleDetails['refTableClass'] == $tableClass) {
                                $selfRef = $ruleDetails['columns'];
                            } else {
                                $relatedRef = $ruleDetails['columns'];
                            }
                        }

                        $m2mValues = $m2mTable->select()
                                              ->from($m2mTable, $relatedRef)
                                              ->where("$selfRef = ?", $primaryKey)
                                              ->query(Zend_Db::FETCH_ASSOC)->fetchAll();

                        $multiOptions = array();
                        foreach ($m2mValues as $_value)
                            $multiOptions[] = $_value[$relatedRef];

                        $entity[$field] = $multiOptions;
                    }
                }

                $form->setDefaults($entity);
            }

            $this->view->assign('form', $form);
            $this->view->assign('entityTitle', $this->_scaffOptions['entityTitle']);
            if (isset($this->_scaffOptions['editLayout']))
                $this->_helper->layout->setLayout($this->_scaffOptions['editLayout']);
        }
    }

    /**
     * Create entity handler.
     */
    public function createAction()
    {
        if (in_array(self::ACTION_CREATE, $this->_scaffOptions['disabledActions']))
            throw new Zend_Controller_Exception('This action is disabled.');

        $info = $this->_getMetadata();

        if (count($info['primary']) > 1) {
            throw new Zend_Controller_Exception('Zend_Controller_Scaffolding does not support compound keys.');
        } elseif (count($info['primary']) == 0) {
            throw new Zend_Controller_Exception('The model you provided does not have a primary key, scaffolding is impossible.');
        }
        
        $form = $this->_initEditForm();

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            list($values, $relData) = $this->_getDbValuesInsert($form->getValues());
            $insertId               = $this->_scaffDb->insert($values);

            // Save many-to-many field to the corresponding table
            if (count($relData)) {
                foreach ($relData as $m2mData) {
                    $m2mTable   = $m2mData[0];
                    $m2mValues  = $m2mData[1];
                    
                    if (count($m2mValues)) {
                        $m2mInfo    = $m2mTable->info();
                        $tableClass = get_class($this->_scaffDb);
                        foreach ($m2mInfo['referenceMap'] as $rule => $ruleDetails) {
                            if ($ruleDetails['refTableClass'] == $tableClass) {
                                $selfRef = $ruleDetails['columns'];
                            } else {
                                $relatedRef = $ruleDetails['columns'];
                            }
                        }

                        foreach ($m2mValues as $v)
                            $m2mTable->insert(array($selfRef => $insertId, $relatedRef => $v));
                    }
                }
            }

            //$this->_helper->FlashMessenger("New {$this->_scaffOptions['entityTitle']} was created.");
            $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
            $flashMessenger->setNamespace('success')->addMessage("New {$this->_scaffOptions['entityTitle']} was created.");
            
            //$this->_redirect($this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/index');
            $moduleName = $this->getRequest()->getModuleName();
            if($moduleName != 'default') {
            	$module = $this->getRequest()->getModuleName() . '/' ;
            }
            $this->_redirect($module. $this->getRequest()->getControllerName() . '/index');
        }

        $this->view->assign('form', $form);
        $this->view->assign('entityTitle', $this->_scaffOptions['entityTitle']);
        if (@$this->_scaffOptions['editLayout'])
            $this->_helper->layout->setLayout($this->_scaffOptions['editLayout']);
    }

    /**
     * Display the list of entries, as well as optional elements
     * like paginator, search form and sortable headers as specified
     * in field definition.
     */
    public function indexAction()
    {
        $fields         = array();
        $searchFields   = array();
        $searchForm     = null;
        $searchActive   = false;

        if (in_array(self::ACTION_INDEX, $this->_scaffOptions['disabledActions']))
            throw new Zend_Controller_Exception('This action is disabled.');

        if ($this->_scaffDb instanceof Zend_Db_Table_Abstract
                || $this->_scaffDb instanceof Zend_Db_Table_Select) {
            if ($this->_scaffDb instanceof Zend_Db_Table_Abstract)
                $select = $this->_scaffDb->select();
            else
                $select = $this->_scaffDb;

            $tableInfo  = $this->_getMetadata();
            $pks        = $tableInfo['primary'];

            // Fetch fields using specified order
            foreach ($tableInfo['cols'] as $columnName) {
                $skip = isset($this->_scaffFields[$columnName]['skip']) ?
                        $this->_scaffFields[$columnName]['skip'] : false;
                if ($skip)
                    if ($skip === true || strtolower($skip) == 'list')
                        continue;

                $order = isset($this->_scaffFields[$columnName]['order']) ?
                         $this->_scaffFields[$columnName]['order'] : null;
                if ($order) {
                    if (!isset($fields[$order]))
                        $fields[$order] = $columnName;
                    else
                        $fields[] = $columnName;
                } else {
                    $fields[] = $columnName;
                }

                // Prepare search form element
                if (isset($this->_scaffFields[$columnName]['searchable'])) {
                    $searchFields[] = $columnName;
                }
            }

            if ($this->_scaffDb instanceof Zend_Db_Table_Abstract)
                $select->from($this->_scaffDb, $fields);
            else
                $select->from($this->_scaffDb->getTable(), $fields);

            /**
             * Apply search filter, storing search criteria in session.
             */
            $searchActive = false;
            if (count($searchFields)) {
                $searchParams   = new Zend_Session_Namespace(self::ID_TOKEN . '_' . $tableInfo['name']);
                $searchForm     = $this->_initSearchForm($searchFields);

                if ($this->getRequest()->isPost() && $searchForm->isValid($_POST)) {
                    $filterFields           = $searchForm->getValues();
                    $searchParams->search   = $filterFields;
                } else {
                    $filterFields = isset($searchParams->search) ? $searchParams->search : array();
                    $searchForm->populate($filterFields);
                }

                foreach ($filterFields as $field => $value) {
                    if ($value) {
                        if (strpos($field, '_' . self::ID_TOKEN . '_from')) {
                            $select->where(str_replace('_' . self::ID_TOKEN . '_from', '', $field) . " >= ?", $value);
                        } elseif (strpos($field, '_' . self::ID_TOKEN . '_to')) {
                            $select->where(str_replace('_' . self::ID_TOKEN . '_to', '', $field) . " <= ?", $value);
                        } else {
                            $metadata = $tableInfo['metadata'];

                            if (in_array($metadata[$field]['DATA_TYPE'], array('char','varchar')))
                                $select->where("$field LIKE ?", $value);
                            else
                                $select->where("$field = ?", $value);
                        }
                        $searchActive = true;
                    }
                }
            }
        } elseif ($this->_scaffDb instanceof Zend_Db_Select) {
            $select = $this->_scaffDb;
            $pks    = array();
            $fields = array_keys($this->_scaffFields);
        }

        /**
         * Handle sorting by modifying SQL and building header sorting links.
         */
        $sortField  = $this->_getParam('orderby');
        $sortMode   = $this->_getParam('mode') == 'desc' ? 'desc' : 'asc';

        if ($sortField) {
            $select->order("$sortField $sortMode");
        }

        $headers = array();

        foreach ($fields as $columnName) {
//            if (!@$this->_scaffFields[$columnName]['hide_in_list'] /*&& !in_array($columnName, $pks)*/) {
                $name = $this->_getColumnTitle($columnName);

                // Generate sorting link
                if (@$this->_scaffFields[$columnName]['sortable']) {
                    // Does a default sorting exist?
                    if (!$sortField && @$this->_scaffFields[$columnName]['sortBy']) {
                        $sortField  = $columnName;
                        $sortMode   = $this->_scaffFields[$columnName]['sortBy'] == 'desc' ? 'desc' : 'asc';
                        $select->order("$sortField $sortMode");
                    }

                    $currentMode = ($sortField == $columnName ? $sortMode : '');

                    if ($currentMode == 'asc') {
                        $counterOrder   = 'desc';
                        $class          = self::ID_TOKEN . '-sort-desc';
                    } elseif ($currentMode == 'desc') {
                        $counterOrder   = 'asc';
                        $class          = self::ID_TOKEN . '-sort-asc';
                    } else {
                        $counterOrder   = 'asc';
                        $class          = '';
                    }

                    $sortParams = array(
                        'orderby'   => $columnName,
                        'mode'      => $counterOrder
                        );

                    $href = $this->view->url($sortParams, 'default');
                    $headers[$columnName] = "<a class=\"" . self::ID_TOKEN . "-sort-link $class\" href=\"$href\">$name</a>";
                } else {
                    $headers[$columnName] = $name;
                }
//            }
        }

        // Enable paginator if needed
        if (isset($this->_scaffOptions['pagination'])) {
        	
            $pageNumber = $this->_getParam('page');
            $paginator = Zend_Paginator::factory($select);

            $paginator->setCurrentPageNumber($pageNumber);
            $itemPerPage = isset($this->_scaffOptions['pagination']['itemsPerPage']) ?
                            $this->_scaffOptions['pagination']['itemsPerPage'] : 10;
            $paginator->setItemCountPerPage($itemPerPage);

            $items = $paginator->getItemsByPage($pageNumber);

            if ($items instanceof Zend_Db_Table_Rowset)
                $items = $items->toArray();
            elseif ($items instanceof ArrayIterator)
                $items = $items->getArrayCopy();

            $entries = $this->_prepareDbRecordsList($items);
            
            $this->view->paginator = $paginator;
            $this->view->pageNumber = $pageNumber;
        } else {
            $entries = $this->_prepareDbRecordsList($select->query()->fetchAll());
        }

        $this->view->headers        = $headers;
        $this->view->entries        = $entries;
        $this->view->entityTitle    = $this->_scaffOptions['entityTitle'];
        $this->view->readonly       = $this->_scaffOptions['readonly'];
        $this->view->searchActive   = $searchActive;
        $this->view->searchForm     = $searchForm;
        $this->view->primaryKey     = $pks;

        $this->view->canCreate      = !in_array(self::ACTION_CREATE, $this->_scaffOptions['disabledActions']);
        $this->view->canUpdate      = !in_array(self::ACTION_UPDATE, $this->_scaffOptions['disabledActions']);
        $this->view->canDelete      = !in_array(self::ACTION_DELETE, $this->_scaffOptions['disabledActions']);
    }

    /**
     * Alias of index action.
     */
    public function listAction()
    {
        if (in_array(self::ACTION_LIST, $this->_scaffOptions['disabledActions']))
            throw new Zend_Controller_Exception('This action is disabled.');

        $this->_forward('index');
    }

    /**
     * Entity deletion handler.
     */
    public function deleteAction()
    {

        if (in_array(self::ACTION_DELETE, $this->_scaffOptions['disabledActions']))
            throw new Zend_Controller_Exception('This action is disabled.');

        $params = $this->_getAllParams();
        $info = $this->_getMetadata();

        if (count($info['primary']) > 1) {
            throw new Zend_Controller_Exception('Zend_Controller_Scaffolding does not support compound keys.');
        } elseif (count($info['primary']) == 0) {
            throw new Zend_Controller_Exception('The model you provided does not have a primary key, scaffolding is impossible!');
        } else {
            // @todo: compound key support
            $primaryKey = null;
            foreach($params AS $k => $v) {
                if(in_array($k, $info['primary'])) {
                    $primaryKey = $v;
                    break;
                }
            }

            try {
                $row = $this->_scaffDb->find($primaryKey)->current();
                $row->delete();         
                //$this->_helper->FlashMessenger("The selected {$this->_scaffOptions['entityTitle']} has been deleted.");
                
                $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
                $flashMessenger->setNamespace('success')->addMessage("The selected {$this->_scaffOptions['entityTitle']} has been deleted.");
                
            } catch (Zend_Db_Exception $e) {
                //$this->_helper->FlashMessenger("The selected {$this->_scaffOptions['entityTitle']} cannot be deleted.");
                
            	$flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
            	$flashMessenger->setNamespace('error')->addMessage("The selected {$this->_scaffOptions['entityTitle']} cannot be deleted.");
            }

            //$this->_redirect($this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/index');
            $moduleName = $this->getRequest()->getModuleName();
            if($moduleName != 'default') {
            	$module = $this->getRequest()->getModuleName() . '/' ;
            }
            $this->_redirect($module. $this->getRequest()->getControllerName() . '/index');
        }
    }

    /**
     * Generates the create/update form based on table metadata
     * and field definitions provided at initialization.
     *
     * @param array $entityData currently editable entity data
     * @return Zend_Form
     */
    private function _initEditForm(array $entityData = array())
    {
        $info       = $this->_getMetadata();
        $metadata   = $info['metadata'];
        $tableClass = get_class($this->_scaffDb);
        $action     = $this->getRequest()->getActionName();
        $form       = array();

        foreach ($metadata as $columnName => $columnDetails) {
            // Primary key is hidden by default
            if($this->_scaffOptions['pkEditable'] == false && in_array($columnName, $info['primary'])) {
                $form['elements'][$columnName] = array(
                    'hidden', array(
                        'value' => 0,
                    )
                );
                continue;
            }

            // Skip the field?
            $skip = isset($this->_scaffFields[$columnName]['skip']) ?
                    $this->_scaffFields[$columnName]['skip'] : false;
            if ($skip)
                if ($skip === true || strtolower($skip) == 'edit')
                    continue;

            // Is the field mandatory?
            if (isset($this->_scaffFields[$columnName]['required'])) {
                if (is_string($this->_scaffFields[$columnName]['required'])) {
                    if ($this->_scaffFields[$columnName]['required'] == 'onCreate' && $action == self::ACTION_UPDATE) {
                        $required = false;
                    }
                } else {
                    $required = $this->_scaffFields[$columnName]['required'];
                }
            } else {
                $required = $columnDetails['NULLABLE'] == 1 ? false : true;
            }

            // Does it have a default value?
            if (!is_null($columnDetails['DEFAULT']))
                $defaultValue = $columnDetails['DEFAULT'];
            else
                $defaultValue = '';

            // Determine if the column is a foreign key and build necessary select/multicheckbox field.
            if (count($info['referenceMap']) > 0) {
            	
                $match = false;
                $refColumnPartner = false;
                foreach ($info['referenceMap'] AS $rule => $ruleDetails) {
                    // Try to find the column in list of foreign key columns.
                    if (is_array($ruleDetails['columns']) && ($colId = array_search($columnName, $ruleDetails['columns'])) !== false) {
                        $refColumnPartner = $ruleDetails['refColumns'][$colId];
                        $match = true;
                    } elseif (is_string($ruleDetails['columns']) && $columnName == $ruleDetails['columns']) {
                        $refColumnPartner = is_array($ruleDetails['refColumns']) ?
                                            array_shift($ruleDetails['refColumns']) : $ruleDetails['refColumns'];
                        $match = true;
                    }
                    
                    if ($match === true && $refColumnPartner !== false) {
                        $options = array();
                        // null allowed?
                        if($columnDetails['NULLABLE'] == 1)
                            $options[""] = "-- Nothing --";
                                           
                        $depModel = new $ruleDetails['refTableClass']();
                        $namingFieldKey = $refColumnPartner;
                        
                        Zend_Debug::dump($depModel);
                        
                        if (isset($depModel->titleField))
                            $namingFieldKey = $depModel->titleField;

                        foreach($depModel->fetchAll()->toArray() AS $k => $v) {
                            $key = $v[$refColumnPartner]; // obtain value of partner column
                            if(!isset($options[$key])) {
                                $options[$key] = $v[$namingFieldKey];
                            }
                        }

                        $form['elements'][$columnName] = array(
                            'select', array(
                                'multiOptions'  => $options,
                                'label'         => $this->_getColumnTitle($columnName),
                                'description'   => $this->_getColumnDescription($columnName),
                                'required'      => $required,
                                'value'         => $defaultValue,
                            )
                        );
                        break;
                    }
                }

                // Foreign key field has been generated, go to next field
                if($match === true) {
                    continue;
                }
            }

            // Build enum column as select or multicheckbox.
            if (preg_match('/^enum/i', $columnDetails['DATA_TYPE'])) {
                // need to extract options
                preg_match_all('/\'(.*?)\'/', $columnDetails['DATA_TYPE'], $matches);

                $options = array();
                foreach ($matches[1] as $match) {
                    $options[$match] = ucfirst($match);
                }

                if (isset($this->_scaffFields[$columnName]['type']) && $this->_scaffFields[$columnName]['type'] == 'radio')
                    $elementType = 'radio';
                else
                    $elementType = 'select';

                $form['elements'][$columnName] = array(
                    $elementType,
                    array(
                        'multiOptions'  => $options,
                        'label'         => $this->_getColumnTitle($columnName),
                        'description'   => $this->_getColumnDescription($columnName),
                        'required'      => $required,
                        'value'         => $defaultValue,
                        'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                           $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                           : array()
                    )
                );

                continue;
            }

            switch ($columnDetails['DATA_TYPE']) {
                // Generate fields for numerics.
                case 'tinyint':
                case 'smallint':
                case 'int':
                case 'mediumint':
                case 'bigint':
                    if (isset($this->_scaffFields[$columnName]['type'])
                            && $this->_scaffFields[$columnName]['type'] == 'checkbox') {
                        $form['elements'][$columnName] = array(
                            'checkbox',
                            array(
                                'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                                   $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                                   : array(),
                                'label'         => $this->_getColumnTitle($columnName),
                                'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array(),
                                'description'   => $this->_getColumnDescription($columnName),
                                'required'      => $required,
                                'value'         => $defaultValue
                            ),
                        );
                    } else {
                        $form['elements'][$columnName] = array(
                            'text',
                            array(
                                'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                                   $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                                   : array(),
                                'label'         => $this->_getColumnTitle($columnName),
                                'description'   => $this->_getColumnDescription($columnName),
                                'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array(),
                                // @todo: unhardcode!
                                'size'          => 10,
                                'required'      => $required,
                                'value'         => $defaultValue,
                            )
                        );
                    }
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $form['elements'][$columnName] = array(
                        'text',
                        array(
                            'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                               $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                               : array(),
                            'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array(),
                            'size'          => 10,
                            'label'         => $this->_getColumnTitle($columnName),
                            'description'   => $this->_getColumnDescription($columnName),
                            'required'      => $required,
                            'value'         => $defaultValue,
                        )
                    );
                    break;

                // Generate single-line input or multiline input for string fields.
                case 'char':
                case 'varchar':
                    $length = $columnDetails['LENGTH'];

                    $form['elements'][$columnName] = array(
                        'text',
                        array(
                            // @todo: unhardcode!
                            'size'          => 40,
                            'label'         => $this->_getColumnTitle($columnName),
                            'description'   => $this->_getColumnDescription($columnName),
                            'required'      => $required,
                            'value'         => $defaultValue,
                            'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array(),
                            'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                               $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                               : array(),
                        )
                    );
                    break;

                case 'smalltext':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $form['elements'][$columnName] = array(
                        'textarea', array(
                            'label'         => $this->_getColumnTitle($columnName),
                            'description'   => $this->_getColumnDescription($columnName),
                            'required'      => $required,
                            'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array(),
                            'value'         => $defaultValue,
                            'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                               $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                               : array(),
                        )
                    );
                    break;

                // Date/time fields.
                case 'date':
                case 'time':
                case 'datetime':
                    $form['elements'][$columnName] = array(
                        'text', array(
                            'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                               $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                               : array(),
                            'label'         => $this->_getColumnTitle($columnName),
                            'description'   => $this->_getColumnDescription($columnName),
                            'required'      => $required,
                            'filters'       => isset($this->_scaffFields[$columnName]['filters']) ? $this->_scaffFields[$columnName]['filters'] : array('StripTags'),
                        )
                    );
                    break;

                default:
                    break;
            }
        }

        // Look for additional field definitions (not from model).
        foreach ($this->_scaffFields as $columnName => $columnDetails) {
            // Determine if the column is a many-to-may relationship item.
            if (isset($columnDetails['dependentTable'])) {

                $dependentTable = $columnDetails['dependentTable'];
                if (!$dependentTable instanceof Zend_Db_Table_Abstract)
                    throw new Zend_Controller_Exception('Zend_Controller_Scaffolding requires a Zend_Db_Table_Abstract model as "dependentTable" field option.');

                $dtInfo = $dependentTable->info();

                foreach($dtInfo['referenceMap'] AS $rule => $ruleDetails) {
                    /**
                     * Try to find the column in list of foreign key columns
                     * that IS NOT referencing current table but another
                     * independent table, and fetch possible values from it.
                     */
                    if (is_string($ruleDetails['columns']) && $tableClass != $ruleDetails['refTableClass']) {
                        $optionsTable = new $ruleDetails['refTableClass'];

                        // Auto-detect PK based on metadata
                        if (!isset($ruleDetails['refColumns'])) {
                            $optionsTableInfo = $optionsTable->info();
                            $ruleDetails['refColumns'] = array_shift($optionsTableInfo['primary']);
                        }

                        // @todo: one column assumed
                        $namingFieldKey = $ruleDetails['refColumns'];
                        
                        Zend_Debug::dump($optionsTable);
                        
                        if (isset($optionsTable->titleField))
                            $namingFieldKey = $optionsTable->titleField;

                        // Value required?
                        $required = isset($columnDetails['required']) ? true : false;

                        $options = array();
                        foreach($optionsTable->fetchAll()->toArray() AS $k => $v) {
                            $key = $v[$ruleDetails['refColumns']];
                            if(!isset($options[$key])) {
                                $options[$key] = $v[$namingFieldKey];
                            }
                        }

                        if ($columnDetails['type'] == 'multicheckbox')
                            $elementType = 'MultiCheckbox';
                        else
                            $elementType = 'Multiselect';

                        $form['elements'][$columnName] = array(
                            $elementType, array(
                                'multiOptions' => $options,
                                'label' => $this->_getColumnTitle($columnName),
                                'description'   => $this->_getColumnDescription($columnName),
                                'required'  => $required,
                                'validators'    => isset($this->_scaffFields[$columnName]['validators']) ?
                                                   $this->_prepareValidators($columnName, $this->_scaffFields[$columnName]['validators'], $entityData)
                                                   : array(),
                            )
                        );
                        break;
                    }
                }
            }
        }

        // Cross Site Request Forgery protection
        $form['elements']['csrf_hash'] = array('hash', array('salt' => 'sea_salt_helps'));

        $form['elements']['submit'] = array(
            'submit',
            array('label' => 'Submit', 'class' => self::ID_TOKEN . '-submit'),
        );

        $form['action'] = $this->view->url();
        $form['class']  = self::ID_TOKEN . '-edit-form';
        
        // Additionally process form
        return $this->_prepareEditForm($form);
    }

    /**
     * Initializes entity search form. Currently supports date and text fields.
     * @param array $fields list of searchable fields.
     * @return Zend_Form instance of form object
     */
    private function _initSearchForm(array $fields)
    {
        $info               = $this->_getMetadata();
        $metadata           = $info['metadata'];
        $datePickerFields   = array();
        $form               = array();
        
        foreach ($fields as $columnName) {
            if (preg_match('/^enum/i', $metadata[$columnName]['DATA_TYPE'])) {
                // need to extract options
                preg_match_all('/\'(.*?)\'/', $metadata[$columnName]['DATA_TYPE'], $matches);

                $options = array('' => 'any');
                foreach ($matches[1] as $match)
                    $options[$match] = $match;

                if (isset($this->_scaffFields[$columnName]['type'])
                        && $this->_scaffFields[$columnName]['type'] == 'radio')
                    $elementType = 'radio';
                else
                    $elementType = 'select';

                $form['elements'][$columnName] = array(
                    $elementType,
                    array(
                        'multiOptions' => $options,
                        'label' => $this->_getColumnTitle($columnName),
                        'class' => self::ID_TOKEN . '-search-' . $elementType
                    )
                );
            } elseif (in_array($metadata[$columnName]['DATA_TYPE'], array('date', 'datetime'))) {
                $form['elements'][$columnName . '_from'] =
                    array(
                        'text', array(
                            'label'         => $this->_getColumnTitle($columnName) . ' from ',
                            'class'         => self::ID_TOKEN . '-search-' . strtolower($metadata[$columnName]['DATA_TYPE']),
                        )
                    );

                $form['elements'][$columnName . '_to'] =
                    array(
                        'text', array(
                            'label' => ' to ',
                            'class' => self::ID_TOKEN . '-search-' . strtolower($metadata[$columnName]['DATA_TYPE']),
                        )
                    );

                $datePickerFields[] = $columnName . '_from';
                $datePickerFields[] = $columnName . '_to';
            } elseif (in_array($metadata[$columnName]['DATA_TYPE'], array('char', 'varchar'))) {
                    $length = $metadata[$columnName]['LENGTH'];

                    $form['elements'][$columnName] = array(
                        'text',
                        array(
                            'class' => self::ID_TOKEN . '-search-text',
                            'label' => $this->_getColumnTitle($columnName),
                        )
                    );
            } else {
                throw new Zend_Controller_Exception("Fields of type {$metadata[$columnName]['DATA_TYPE']} are not searchable.");
            }
        }

        $form['elements']['submit'] = array(
            'submit',
            array(
                'ignore'   => true,
                'class' => self::ID_TOKEN . '-btn-search',
                'label' => 'Search',
            )
        );

        $form['elements']['reset'] = array(
            'submit',
            array(
                'ignore'   => true,
                'class' => self::ID_TOKEN . '-btn-reset',
                'label' => 'Reset',
                'onclick' => 'ssfResetForm(this.form);'
            ),
        );

        if (count($datePickerFields))
            $this->_loadDatePicker($datePickerFields);

        $form['action'] = $this->view->url();
        $form['class']  = self::ID_TOKEN . '-search-form';
        return $this->_prepareSearchForm($form);
    }

    /**
     * Filters form values making them ready to be used by Zend_Db_Table_Abstract.
     *
     * @param Array $values form values
     * @return Array $values filtered values
     */
    private function _getDbValues(array $values)
    {
        if (count($values) > 0) {
            unset($values['csrf_hash']);
            unset($values['submit']);
        }

        return $values;
    }

    /**
     * Prepare form values for insertion. Applies field save modifiers
     * and handles many-to-many synthetic fields.
     *
     * @param Array $values initial values
     * @return Array $values modified values
     */
    private function _getDbValuesInsert(array $values)
    {
        $values = $this->_getDbValues($values);
        $relData= array();
        
        if (count($values) > 0) {
            $info = $this->_getMetadata();
            foreach ($info['primary'] AS $primaryKey) {
                unset($values[$primaryKey]);
            }
        }

        foreach ($values AS $k => $v) {
            // Many-to-many field has to be saved into another table
            if (isset($this->_scaffFields[$k]['dependentTable'])) {
                $relData[] = array($this->_scaffFields[$k]['dependentTable'], $v);
                unset($values[$k]);
            } else {
                // Apply field modifier if any
                if (isset($this->_scaffFields[$k]['saveModifier']))
                    $values[$k] = call_user_func($this->_scaffFields[$k]['saveModifier'], $v);
            }
        }

        return array($values, $relData);
    }

    /**
     * Prepare form values for update. Applies field save modifiers
     * and handles many-to-many synthetic fields.
     *
     * @param Array $values initial values
     * @return Array modified values in form array($values => Array, $where => String)
     */
    private function _getDbValuesUpdate(array $values)
    {
        $values = $this->_getDbValues($values);
        $info   = $this->_getMetadata();
        $where  = array();
        $update = array();
        $relData= array();

        foreach ($values AS $k => $v) {
            if (in_array($k, $info['primary'])) {
                // PK has to be used in where clause
                $where[] = $this->_scaffDb->getAdapter()->quoteInto("$k = ?", $v);
            } elseif (in_array($k, $info['cols'])) {
                // Normal table field has to be directly saved
                if (!(isset($this->_scaffFields[$k]['required']) && $this->_scaffFields[$k]['required'] == 'onCreate' && empty($v)))
                    // Apply field modifier if any
                    if (isset($this->_scaffFields[$k]['saveModifier']))
                        $update[$k] = call_user_func($this->_scaffFields[$k]['saveModifier'], $v);
                    else
                        $update[$k] = $v;
            } elseif (isset($this->_scaffFields[$k]['dependentTable'])) {
                // Many-to-many field has to be saved into another table
                $relData[] = array($this->_scaffFields[$k]['dependentTable'], $v);
            }
        }

        if (count($where) > 0) {
            $where = implode(" AND ", $where);
            return array($update, $where, $relData);
        } else {
            return array(null, null, null);
        }
    }

    /**
     * Prepares the list of records. Optionally applies field listing modifiers.
     *
     * @param Array $entries entries to be displayed
     * @return Array $list resulting list of entries
     */
    private function _prepareDbRecordsList(array $entries)
    {
        $info = $this->_getMetadata();
        $list = array();

        foreach ($entries AS $entry) {
            $keys = array();
            foreach ($entry AS $field => $value) {
                // Show only visible fields
                $skip = isset($this->_scaffFields[$field]['skip']) ?
                        $this->_scaffFields[$field]['skip'] : false;
                if ($skip)
                    if ($skip === true || strtolower($skip) == 'list')
                        continue;

                // Call list view modifier for specific column if set
                if (isset($this->_scaffFields[$field]['listModifier']))
                    $row[$field] = call_user_func($this->_scaffFields[$field]['listModifier'], $value);
                else
                    $row[$field] = $value;

                if (is_array($info) && in_array($field, $info['primary'])) {
                    $keys[$field] = $value;
                }
            }

            $row['pkParams'] = $keys;

            $list[] = $row;
        }

        return $list;
    }

    /**
     * Retrieve model table metadata.
     * @return Array
     */
    private function _getMetadata()
    {
        if (is_null($this->_scaffMeta)) {
            if ($this->_scaffDb instanceof Zend_Db_Table_Abstract) {
                $this->_scaffMeta = $this->_scaffDb->info();
            } elseif ($this->_scaffDb instanceof Zend_Db_Table_Select) {
                $this->_scaffMeta = $this->_scaffDb->getTable()->info();
            }
        }

        return $this->_scaffMeta;
    }

    /**
     * Looks if there is a custom defined name for the column for displaying
     * @param String $columnFieldName
     * @return String $columnLabel
     */
    private function _getColumnTitle($columnName)
    {
        if (isset($this->_scaffFields[$columnName]['title']))
            return $this->_scaffFields[$columnName]['title'];
        else
            return ucfirst($columnName);
    }

    /**
     * Looks if there is a custom defined name for the column for displaying
     * @param String $columnFieldName
     * @return String $columnLabel
     */
    private function _getColumnDescription($columnName)
    {
        if (isset($this->_scaffFields[$columnName]['description'])) {
            return $this->_scaffFields[$columnName]['description'];
        }
        return null;
    }

    /**
     * Additionally handles validators (adds/removes options if needed).
     *
     * @param String $field database field name
     * @param array $validators list of custom validators
     * @param array $entityData entity record
     */
    private function _prepareValidators($field, $validators, $entityData)
    {
        if (is_array($validators)) {
            foreach ($validators as $i => &$validator) {
                // Validation options provided
                if (is_array($validator)) {
                    // Add exclusion when validating existing value
                    if ($validator[0] == 'Db_NoRecordExists') {
                        if ($this->getRequest()->getActionName() == self::ACTION_UPDATE)
                            $validator[2]['exclude'] = array('field' => $field, 'value' => $entityData[$field]);
                    }
                }
            }
        } else {
            $validators = array();
        }

        return $validators;
    }

    /**
     * Builds the edition form object. Use this method to apply custom logic like decorators etc.
     *
     * @param array $form form configuration array
     * @return Zend_Form instance of Zend_Form
     */
    protected function _prepareEditForm(array &$form)
    {
        $formObject = new Zend_Form($form);

        // Add required flag
        foreach ($formObject->getElements() as $element) {
            $label = $element->getDecorator('Label');
            
            // remove decorators => Sandy
            $element->removeDecorator('label')->removeDecorator('htmlTag');
            $element->removeDecorator('DtDdWrapper');
            
            if (is_object($label)) {
                $label->setOption('placement', 'prepend');
                $label->setOption('requiredSuffix', ' *');
            }
        }
        
        return $formObject;
    }

    /**
     * Builds the search form object. Use this method to apply custom logic like decorators etc.
     *
     * @param array $form form configuration array
     * @return Zend_Form instance of Zend_Form
     */
    protected function _prepareSearchForm(array &$form)
    {
        $formObject = new Zend_Form($form);
        return $formObject;
    }

    /**
     * Allows to initialize a JavaScript date picker. Typically you should
     * include here necessary JS files.
     *
     * @param array $fields fields to use date picking
     */
    protected function _loadDatePicker(array $fields)
    {
    }
}

?>
