<?php
/**
 * @desc application class file,this is entry for app
 * @author nana
 * @date 2011
 *
 */
require_once(DIR_CORE.'bootstrap.php');
class NICED_Application 
{
	public    static $controller;
    protected static $container;
    protected static $instance;
	
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new NICED_Application();
        }

        return self::$instance;
    }


	/**
	 * @desc application enter
	 * @todo error page
	 */
	public static function execute() 
	{
		try 
		{
            $bootstrap = NICED_Bootstrap::getInstance();
            $bootstrap->init();
            self::$container = new Container();

			$request      = NICED_Request::getInstance();
            $request_name = $request->getRequestName();

			while (true)
			{
				$controller = self::getController($request_name, self::$container);

				if (method_exists($controller, 'forward'))
				{
					$request_name = $controller->forward();
					continue;
				}
				
                self::dispatch($controller);//$controller->execute();
				break;
			}

		} 
		catch (Exception $e) 
		{
			if (SC::get('board_config.debug')) 
			{
				$controller    = new NICED_Controller();
				$die_view_name = $controller->getMessageDieViewName();
				$view          = $controller->instantiate($die_view_name, 'view', $controller);
				$view->set('exception', $e);
				$view->execute();
			} 
			else 
			{
				echo 'error!';//error page
			}
		}
	}

    public static function dispatch($controller)
    {
		$request_name = $controller->getRequestName();
        //call action's execute method
        $action = $controller->instantiate($request_name, 'action', $controller);
        if ($action instanceof NICED_Action)
        {
            $result = $action->execute();
        }
        else 
        {
            throw new NICED_NotExistsException('instantiate action fail', $request_name);
        }

        $view_name = $controller->retrieveViewName($request_name, $result);

        if (!$view_name) 
        {
            throw new NICED_ExistsException('instantiate view '.$view_name.' fail!');
        }

        $view = $controller->instantiate($view_name, 'view', $controller);

        if ($view instanceof NICED_View) 
        {
            $view->execute();
        } 
        else 
        {
            throw new NICED_NotExistsException('instantiate view '.$view_name.' fail!');
        }
    }


    public static function getController($request_name, $container = null) 
	{
		try
		{
			$controller = NICED_Controller::instantiate($request_name, 'controller', $container);
		}
		catch(Exception $e)
		{
			
		}

		if (!self::$controller)
		{
			self::$controller = self::retrieveController($request_name, $container);
		}

		if (!self::$controller)
		{
			throw new NICED_NotExistsException('create controller '.$request_name.' fail');
		}

		return self::$controller;
	}

	protected static function retrieveController($request_name, $container = null) 
	{
        $arr         = NICED_Request::parseRequestName($request_name);
		$app_name    = isset($arr[0]) ? strtolower($arr[0]) : null;

		if (!$app_name)
		{
			throw new NICED_ValidationException('app name can not be null');
		}

		$class_name = ucwords($app_name).'_Controller';
		
		if (!class_exists($class_name)) 
		{
			$file_1 = DIR_APPS . $app_name . '/'.$app_name.'.controller.php';
			$file_2 = DIR_APPS . $app_name . '/controllers/'.$app_name.'.controller.php';
			$file = null;

			if (file_exists($file_1)) 
			{
				$file = $file_1;
			} 
			else if (file_exists($file_2)) 
			{
				$file = $file_2;
			}

			if ($file) 
			{
				include_once $file;
			}
		}

		if (!class_exists($class_name)) 
		{
			throw new NICED_NotExistsException('Controller not exist', $class_name);
		}

		return new $class_name($request_name, $container);
	}

}//end class
