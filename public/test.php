<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once '../tests/MyTest.php';
require_once 'PHPUnit/Autoload.php';


$suite  = new PHPUnit_Framework_TestSuite("MyTest");
$suite->run();
