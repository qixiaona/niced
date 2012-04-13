<?php
/**
 * @desc bootstrap class file
 * @author nana
 * @date 2011
 *
 */
class NICED_Bootstrap
{
	protected static $inited = false;
	protected static $instance = null;
	protected static $autoloadDirs = array();


	public function __construct()
	{
		//self::init();
	}

	public static function getInstance()
	{	
		if (!self::$instance)
		{
			$classname = __CLASS__;
			self::$instance = new $classname();
		}

		return self::$instance;
	}

	protected static function getLoadFiles()
	{
		$files = array(
			DIR_CORE . 'container.php',
			DIR_CORE . 'config.php',
			DIR_CORE . 'mvc/controller.php',
			DIR_CORE . 'mvc/model.php',
			DIR_CORE . 'mvc/view.php',
			DIR_CORE . 'mvc/action.php',
			DIR_CORE . 'request.php',
			DIR_CORE . 'exception.php',
			DIR_CORE . 'url.php',
			DIR_CORE . 'layoutmanager.php',
		);

		return $files;
	}

	protected static function initAutoLoadDir()
	{
		self::$autoloadDirs = array(
			0 => DIR_CORE,
			1 => DIR_LIBRARY,
			2 => DIR_CLASSES,
			3 => DIR_HELPER,
			4 => DIR_UTIL,
		);	

		return true;
	}


	/**
	 * @desc 初始化
	 * @return void
	 */
	public static function init() 
	{
		if (self::$inited) 
		{
			return true;
		}

		//default load some files,include class and function lib
		$files = self::getLoadFiles();
		foreach ($files as $s)
		{
			require_once $s;
		}
		
		//init autoload dirs
		self::initAutoLoadDir();

		//init config,load from config dir to array or memcache or other type
        $config = NICED_Config::getInstance();
        $config->init();

		//next all, must be after init config,caz need to load board_config
		SC::set('board_config.time_now', time());//add system time

		if (SC::get('board_config.debug'))
		{
			ini_set('display_error', true);
			error_reporting(E_ALL);
		}

		self::$inited = true;
	}

	/**
	 * @desc for spl auto load
	 * @return void
	 */
	public static function autoloadClass($class_name) 
    {
		//all case for special files, maybe one file has some class
		switch (strtolower($class_name)) 
        {
		    case 'niced_dbfactory':
    		case 'niced_pdo': 
			{
				return include(DIR_CORE.'database.php');
				break;
			}
		    case 'niced_util' : 
			{
				return include(DIR_LIBRARY.'util.php');
				break;
			}
    		case 'niced_dao' :
    		case 'niced_daofactory' : 
			{
				return include(DIR_CORE.'dao.php');
				break;
			}
	    	case 'memcache_g' :
		    case 'memcachedisabled' :
            case 'namespacedmemcache' : 
		    case 'replicamemcache' :
    		case 'memcachefactory' : 
			{
				return include_once(DIR_LIBRARY.'memcache.php');
				break;
			}
	    	default :
		    {
			    foreach (self::$autoloadDirs as $dir)
			    {
				    $filename = $dir.strtolower($class_name).".php";

				    if (file_exists($filename))
    				{
	    				require_once($filename);
		    			break;
			    	}
			    }
		    }
    	} //end switch

    }

}//end class

spl_autoload_register(array('NICED_Bootstrap', 'autoloadClass'));
