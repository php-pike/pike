<?php

if(false === is_dir(__DIR__ . '/../vendor')) {
    throw new RuntimeException('You should run: php composer.phar install first'
            . ' to load mandatory dependencies.');
}

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(__DIR__ . '/../application'));

defined('LIBRARY_PATH')
    || define('LIBRARY_PATH', realpath(__DIR__ . '/../lib'));

define('APPLICATION_ENV', 'testing');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../vendor'),
    realpath(__DIR__ . '/../lib'),
    get_include_path(),
)));

include_once 'autoload.php'; //composer
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Pike');

Zend_Session::start();
