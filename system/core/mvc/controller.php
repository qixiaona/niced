<?php
/**
 * @desc controller class file
 * @author nana
 * @date 2011
 *
 */
class NICED_Controller
{
    protected static $_container;
    protected     $_requestName;
	protected     $_models;
	protected     $_exception;

	public function __construct($request_name = null, &$container = null)
	{
        $this->_requestName = $request_name;
        $this->_container   = &$container;
	}
	
	public function execute() 
	{
		if ($this->_requestName)
		{
			$this->dispatch($this->_requestName);
		}
		else
		{
			throw new NICED_ValidationException('no action name');
		}
	}

	public function dispatch($request_name = null) 
	{
		//action
		$action = $this->instantiate($request_name, 'action', $this);

		if ($action instanceof NICED_Action) 
		{
			$result = $action->execute();
		} 
		else 
		{
			throw new NICED_NotExistsException('instantiate action fail');
		}

		//view
		$view_name = $this->retrieveViewName($request_name, $result);

		if (!$view_name) 
		{
			throw new NICED_NotExistsException('retrieve view name '.$view_name.' fail!');
		}
		
		$view = $this->instantiate($view_name, 'view', $this);

		if ($view instanceof NICED_View) 
		{
			$view->execute();
		} 
		else
		{
			throw new NICED_NotExistsException('instantiate view '.$view_name.' fail!', $view_name);
		}
	}
	
	public function selectView($result)
	{
		return $this->_requestName;
	}

	public function getRequest()
	{
		return NICED_Request::getInstance();
	}

    public function getRequestName()
    {
        return $this->_requestName;
    }

	public function getMessageDieViewName()
	{
		return 'Default.MessageDie';
	}
	
	public function getModel($model_name) 
	{
		$model_name = strtolower($model_name);
        $model = $this->_container->get('models.'.$model_name);

		if ($model) 
		{
			return $model;
		}

		$model = $this->instantiate($model_name, 'model', $this);

		if ($model instanceof NICED_Model) 
		{
            $this->_container->set('models.'.$model_name, $model);

			return $model;
		}
		else
		{
			throw new NICED_NotExistsException('get model '.$model_name.' fail, not exists', $model_name);
		}
	}	
	 
    public static function instantiate($request_name, $type, $param = null, $only_include = false) 
	{
		NICED_Request::checkRequestName($request_name);

        $class_name = str_replace('.', '_', ucwords($request_name) ) . '_' . ucwords($type);
		$name       = strtolower($request_name);

        if(!class_exists($class_name))
        {
			$paths = self::getInstantiateFilePath($name, $type);
			$is_included_file = false;//is included file

			foreach ($paths as $path)
			{
				if (file_exists($path)) 
				{
					include_once($path);
					$is_included_file = true;
					break;
				}			
			}

			if (!$is_included_file)
			{
				throw new NICED_NotExistsException($class_name.' class file not exists, search path is :'.var_export($paths, true));
			}
        }

		if ($only_include)
		{
			return true;
		}

        if(class_exists($class_name)) 
        {
            if ('controller' == $type)
            {
                return new $class_name($request_name, $param);
            }
            else
            {
                return new $class_name($param);
            }
        }
        else
        {
            throw new NICED_NotExistsException($class_name.' '.$type.' class does not exists');
        }
    }

	public static function getInstantiateFilePath($request_name, $type)
	{
		$types = array('controller', 'action', 'model', 'view', 'template');
		if (!in_array($type, $types))
		{
			throw new NICED_ValidationException('type is invalid!');
		}

		NICED_Request::checkRequestName($request_name);

		$request_name = strtolower($request_name);
		$paths        = array();

		list($base, $name) = explode('.', $request_name, 2);
		$name              = str_replace('.', '/', $name);

		$paths[] = DIR_APPS . $base . '/' . $name . '.' . $type . '.php';
		$paths[] = DIR_APPS . $base . '/' . $type . 's/' . $name . '.' . $type . '.php';	

		return $paths;
	}

	public function retrieveViewName($request_name, $action_result) 
	{
		$method = 'selectView_' . str_replace('.', '_', $request_name);

		if (!method_exists($this, $method)) 
		{
			$method = 'selectView';
		} 

		return $this->$method($action_result);
	}

	//for handle exception in action, and get exception in view
	public function handleException($e)
	{
		$this->_exception = $e;
	}

	public function getException()
	{
		return $this->_exception;
	}
} //end class
