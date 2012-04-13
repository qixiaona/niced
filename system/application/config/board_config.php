<?php
if (!defined('NICED_IN')) die('Access Denied!');
$cfg = array();
$cfg['default_request_name'] = 'sample.index';
$cfg['default_app']          = 'sample';
$cfg['default_action']       = 'index';
$cfg['page_size'] = 100; //列表条数
$cfg['version']   = 100; //version
$cfg['debug']     = true;

$cfg['index_page_name'] = "";//如index.php

//static files base path,relative document root
$cfg['path_site']        = "";
$cfg['path_public_html'] = '';
$cfg['path_static']      = $cfg['path_public_html'].'/static';

$cfg['path_css']         = $cfg['path_static']."/css";
$cfg['path_js']          = $cfg['path_static']."/js";
$cfg['path_image']       = $cfg['path_static']."/images";

//disable for apps
$cfg['onlinedemo_disable'] = 1;
