<?php
/**
 * @desc request class file
 * @author nana
 * @date 2011
 *
 */
class NICED_Request extends Container
{

	protected static $_instance;
	
	
	protected $_app;
	protected $_action;
    protected $_requestName;
	protected $_uri;

	protected static $_requestNameSeparator = '.';

	public static function getInstance() 
	{
		if (!isset(self::$_instance) || !is_object(self::$_instance)) 
		{
			self::$_instance = new NICED_Request();
		}

		return self::$_instance;
	}
	
	public function __construct() 
	{
		$base_url = NICED_URL::getBaseURL(false, true);
		$uri = $this->getURI();

		if (trim($base_url, '/'))
		{
			if (false !== strpos($uri, rtrim($base_url, '/'))) 
			{
				$start = strpos($base_url, rtrim($uri)) + strlen($base_url);
				$uri = substr($uri, $start);//if url is http://hostname/hello/user, $uri=hello/user
			}
			else 
			{
				throw new NICED_Exception('URI解析错误，请联系管理员！');
			}
		}

		$trim_chars = "/\n\r\0\t\x0B ";
		$url_args = array();
		$router_table = LC::get('router');
		list($url_path, ) = explode('?', $uri, 2);//去除提交的参数
		$url_path = trim($url_path, '/');

		while (strlen($url_path) != 0 && !(isset($router_table[$url_path]))) 
		{
            $new_arg = substr($url_path, strrpos($url_path, '/'));
            $url_path = substr($url_path, 0, strrpos($url_path, $new_arg));
            array_unshift($url_args, trim($new_arg, $trim_chars));
		}

		if ($url_path != '') 
		{
			$route = strtolower($router_table[$url_path]);
			$this->_requestName = $route;

			if (strpos($route, self::$_requestNameSeparator)) 
			{
				list($this->_app, $this->_action) = explode(self::$_requestNameSeparator, $route);
			}
		} 
		
		if (!$this->_app || !$this->_action) //get default value in board_config
		{ 
			$this->_app         = SC::get('board_config.default_app');
			$this->_action      = SC::get('board_config.default_action');
			$this->_requestName = (SC::get('board_config.default_request_name')) ? SC::get('board_config.default_request_name') : self::generateRequestName(array(SC::get('board_config.default_app'), SC::get('board_config.default_action')));
		}

		$this->set('__args__', $url_args);
	}

	protected function getURI()
	{
		if ('cli' === PHP_SAPI)
		{
			$this->_uri = $_SERVER['argv'][1];
			//remove GET param from uri
			if (($query = strpos($this->_uri, '?')) !== false)
			{
				list ($this->_uri, $query) = explode('?', $this->_uri, 2);
				// Parse the query string into $_GET
				parse_str($query, $_GET);
				parse_str($query, $_REQUEST);
			}
		}
		else
		{
			$this->_uri = $_SERVER['REQUEST_URI'];
		}

		return $this->_uri;
	}

    public function getRequestName()
    {
        return $this->_requestName;
    }

    public static function generateRequestName($arr)
    {
        return implode(self::$_requestNameSeparator, $arr);
    }

    public static function parseRequestName($request_name)
    {
        $slice = explode(self::$_requestNameSeparator, $request_name);
        
        return $slice;
    }

	public static function checkRequestName($request_name)
	{
        if(strlen($request_name) < 1)  
		{
			throw new NICED_ValidationException('invalid invalid name : '.$request_name);
		}

		if(!strpos($request_name, self::$_requestNameSeparator)) 
		{
			throw new NICED_ValidationException('invalid request name: '.$request_name);
		}	

		return true;
	}

	public function loadArg($index, $key, $default = null, $filter = 'safe') 
	{
		$args  = $this->get('__args__');
		$index = (int)$index;

		if (isset($args[$index])) 
		{
			$value = $args[$index];
		} 
		else 
		{
			$value = $default;
		}

		if (!is_null($value)) 
		{
			$value = $this->filterParam($value, $filter, $default);
		}

		$this->set($key, $value);

		return $value;
	}
	
	public function loadParam($key, $default = null, $filter = 'safe', $source = 'request') 
	{
		switch (strtolower($source)) 
		{
			case 'post':
			{
				$params = $_POST;
				break;
			}
			case 'get':
			{
				$params = $_GET;
				break;
			}
			case 'cookie':
			{
				$params = $_COOKIE;
				break;
			}
			case 'server':
			{
				$params = $_SERVER;
				break;
			}
			case 'env':
			{
				$params = $_ENV;
				break;
			}
			case 'file':
			{
			}
			case 'files':
			{
				$params = $_FILES;
				break;
			}
			default:
			{
				$params = $_REQUEST;
				break;
			}
		} //end switch
		
		if (isset ($params[$key])) 
		{
			$value = $params[$key];
		} 
		else 
		{
			$value = $default;
		}

		if (!is_null($value)) 
		{
			$value = $this->filterParam($value, $filter, $default);
		}

		$this->set($key, $value);

		return $value;
	}

	protected function filterParam($value, $filter, $default_value) 
	{
		if(is_array($filter)) 
		{
			switch (key($filter)) 
			{
				case 'regex' :
				{
					$pattern = $filter['regex'];
					$filter = 'regex';
					break;
				}
				case 'enum' :
				{
					$pattern = $filter['enum'];
					$filter = 'enum';
					break;
				}
				default :
				{
				}
			}
		}
		
		switch ($filter) 
		{
			case 'alpha' :
			{
				return ctype_alpha($value) ? $value : $default_value;
			}
			case 'int' :
			{
				return (ctype_digit($value) || ($value[0] == '-' && ctype_digit(substr($value, 1)))) ? $value : $default_value;
			}
			case 'posint' :
			{
				return ctype_digit($value) ? $value : $default_value;
			}
			case 'numeric' :
			{
				return is_numeric($value) ? $value : $default_value;
			}
			case 'alphanum' :
			{
				return ctype_alnum($value) ? $value : $default_value;
			}
			case 'bool' :
			{
				return $value ? TRUE : FALSE;
			}
			case 'enum' :
			{
				return in_array($value, $pattern) ? $value : $default_value;
			}
			case 'regex' :
			{
				return preg_match($pattern, $value) ? $value : $default_value;
			}
			case 'raw' :
			{
				return $value;
			}
			case 'safe' :
			{
			}
			default :
			{
				$value = str_replace (array('<', '>', '"', "'", '{', '('), '', $value);//, '&', '%'

				return ($value || 0 == $value) ? $value : $default_value;// set to default value if there is nothing left after filtering
			}
		} //end switch
	}
} //end class
