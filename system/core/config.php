<?php
/**
 * @desc config class file
 * @author nana
 * @date 2011
 *
 */
class NICED_Config extends Container 
{
	protected static $_instance;
	
	public static function getInstance() 
	{
		if (!isset(self::$_instance) || !is_object(self::$_instance)) 
		{
			self::$_instance = new NICED_Config();
		}

		return self::$_instance;
	}

	public static function init()
	{
		self::loadConfig();
	}
	
	public function __construct() 
	{
		//self::loadConfig();//load config
	}
	
	public function get($k) 
	{
		$v = parent::get($k);
		if ($v === null) 
		{
			throw new NICED_Exception('config ' . $k . 'not exist');
		}

		return $v;
	}
	
	/**
	 * @desc load all config from config files
	 * @return void
	 * 
	 */
	public static function loadConfig() 
	{
		$app_config_dir     = DIR_CONFIG;//config path

		if (is_dir($app_config_dir)) 
		{
			if ($dh = opendir($app_config_dir)) 
			{
				while (($filename = readdir($dh)) !== false) //loop all config files
				{ 
					if ('.' == $filename || '..' == $filename || '.' == substr($filename, 0, 1)) 
					{
						continue;
					}

					$filename = strtolower($filename);
					$type = basename($filename, '.php');
					require_once($app_config_dir.$filename);//include file

					switch ($type) 
					{
						case 'board_config' : //board_config
						{ 
							SC::set('board_config', $cfg);
							break;
						}
						case 'router' : //app router
						{ 
							foreach ($cfg as $key => $value) 
							{
								LC::set('router.'.$key, strtolower($value));
							}
							
							break;
						}
						case 'config' : //lc config for app
						{ 
							LC::set('config', $cfg);
							break;
						}
						case 'server': 
						{
							NICED_DBFactory::setConfig($type, $cfg);
							break;
						}
						case 'dsn': 
						{
							NICED_DBFactory::setConfig($type, $cfg);
							break;
						}
						case 'dao': 
						{
							NICED_DaoFactory::setDaoConfig($cfg);	
							break;
						}
						default: 
						{
							
						}
					}			
				}

				closedir($dh);

				if (!NICED_DBFactory::$populated) 
				{
					NICED_DBFactory::resolve();
				}
			}
		}
	}

} //end class
