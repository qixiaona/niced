<?php
//mysql
$cfg = array();
$cfg['default'] = array(
						'type' => 'mysql',
						'dbname' => 'default',
						'server_id' => '0',
						'failover_server_id' => '0',
					 ); 

//memcache
$cfg['query_memcache'] = array(
	'type' => 'memcache',
	'hosts' => array(
				'127.0.0.1:11211:1',
				'127.0.0.1:11212:1',
			   )
);

