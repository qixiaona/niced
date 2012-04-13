<?php
/**
 * @desc: 常量定义
 *
 */
//设置域名
define('DOMAIN', 'domain');
define('TLD', 'com');
define('SERVER_NAME_SUFFIX', DOMAIN.'.'.TLD);
define('PROTOCOL_HTTP', 'http');

$www_server_name   = "domain";
$css_server_name   = "domain";
$image_server_name = "domain";
$css_server_name   = "domain";


define('MAIN_SERVER', $www_server_name.".".SERVER_NAME_SUFFIX);
define('JS_SERVER', $css_server_name.".".SERVER_NAME_SUFFIX);
define('IMAGE_SERVER', $image_server_name.".".SERVER_NAME_SUFFIX);
define('CSS_SERVER', $css_server_name.".".SERVER_NAME_SUFFIX);

define('MAIN_DOMAIN', PROTOCOL_HTTP.'://'.MAIN_SERVER);
define('JS_DOMAIN', PROTOCOL_HTTP.'://'.JS_SERVER);
define('IMAGE_DOMAIN', PROTOCOL_HTTP.'://'.IMAGE_SERVER);
define('CSS_DOMAIN', PROTOCOL_HTTP.'://'.CSS_SERVER);