<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));


/*
$incArray = array(	'/models/MongoDoc');

foreach($incArray as $fileLocPath) {
	$path = APPLICATION_PATH . $fileLocPath;
	set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}
*/


/** Zend_Application */
require_once 'Zend/Application.php';

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Shanty_');
$loader->registerNamespace('Lex0r_');

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);


$application->bootstrap()
            ->run();
