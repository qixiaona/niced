<?php
/** 
  * @desc this file position effect board_config file some config, $config['public_html'],etc.
  *
  */
date_default_timezone_set("Asia/Shanghai");
$start = microtime(true);
define('DS', DIRECTORY_SEPARATOR);

//define system path
//define('DIR_SYSTEM',   realpath('../../../').DS.'system'.DS);//if use ln use this
define('DIR_SYSTEM',   realpath('../../').DS.'system'.DS);//if use cp use this

//if change apps dir to other path, u can define apps path
//define('DIR_APPS',   '/var/www/html/niced/apps/');

//include global file
require realpath('../') .DS.'include'.DS.'global.inc.php';
require_once DIR_CORE . 'application.php';

NICED_Application::execute();

//var_dump((microtime(true) - $start) * 1000 . ' ms');
