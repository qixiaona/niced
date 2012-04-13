<?php
define('NICED_IN', true);
if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

//define root dir
define('DIR_ROOT', dirname(dirname(__FILE__)) . DS);

//一级主目录
define('DIR_CLASSES', DIR_ROOT . 'classes' . DS);
//define('DIR_APPS', DIR_ROOT . 'apps' . DS);
define('DIR_PUBLIC', DIR_ROOT . 'public_html' . DS);
define('DIR_INCLUDE', DIR_ROOT."include".DS);
if (!defined('DIR_APPS'))
{
	define('DIR_APPS', DIR_ROOT . 'apps' . DS);
}
if (!defined('DIR_CONFIG'))
{
	define('DIR_CONFIG', DIR_ROOT . 'config' . DS);
}

//system下的目录
define('DIR_CORE', DIR_SYSTEM  . 'core' . DS);
define('DIR_LIBRARY', DIR_SYSTEM . 'library' . DS);

//class下的目录
define('DIR_HELPER', DIR_CLASSES."helper".DS);
define('DIR_UTIL', DIR_CLASSES."util".DS);//工具类

require_once(DIR_INCLUDE.'constant.php');
