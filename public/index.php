<?php
/**
 * 入口文件
 */
error_reporting(E_ALL & ~ E_STRICT & ~ E_NOTICE);

header('Content-type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');     // HTTP/1.1
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');       // Date in the past
header('Pragma: no-cache');

define('ROOT_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', ROOT_PATH . '/application');
define('CONF_PATH', ROOT_PATH . '/conf');
define('LOG_PATH', ROOT_PATH . '/log');
define('DATA_PATH', ROOT_PATH . '/data');
define('THIRD_PATH', ROOT_PATH . '/thirdparty');


$application = new Yaf_Application( CONF_PATH . "/application.ini");
$application->bootstrap()->run();
