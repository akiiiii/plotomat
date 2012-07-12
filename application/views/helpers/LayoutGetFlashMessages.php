<?php

/*
 * http://www.zfforum.de/showthread.php?t=6190
 */

class My_View_Helper_LayoutGetFlashMessages extends Zend_View_Helper_Abstract 
{
    public function LayoutGetFlashMessages()
    {
		$flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
		$messagesArray = array();
		
		if ($flashMessenger->setNamespace('error')->hasMessages()) {
			$messages = $flashMessenger->getMessages();
			foreach($messages as $message) {
				$messagesArray['error'][] = $message;
			}
		}
		
		if ($flashMessenger->setNamespace('warning')->hasMessages()) {
			$messages = $flashMessenger->getMessages();
			foreach($messages as $message) {
				$messagesArray['warning'][] = $message;
			}
		}
		
		if ($flashMessenger->setNamespace('success')->hasMessages()) {
			$messages = $flashMessenger->getMessages();
			foreach($messages as $message) {
				$messagesArray['success'][] = $message;
			}
		}
		
		if ($flashMessenger->setNamespace('info')->hasMessages()) {
			$messages = $flashMessenger->getMessages();
			foreach($messages as $message) {
				$messagesArray['info'][] = $message;
			}
		}

		return $messagesArray;
    }
}
