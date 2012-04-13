<?php
/**
 * @desc view class file
 * @author nana
 * @date 2011
 *
 */
abstract class NICED_View extends Container
{
	
	protected $_controller;
	
	public function __construct(NICED_Controller $c) 
	{
		$this->_controller = $c;
	}
	
	abstract public function execute();	

	protected function getController() 
	{
		return $this->_controller;
	}	
	
	protected function render($template, $strict = true) 
	{
		if (!is_array($template)) 
		{
			$template = array($template);
		}

		$file = null;

		foreach ($template as $t) 
		{
			$name = strtolower($t);
            NICED_Request::checkRequestName($name);
			$paths = NICED_Controller::getInstantiateFilePath($name, 'template');
			$is_included_file = false;//是否已经包含进文件

			foreach ($paths as $path)
			{
				if (file_exists($path)) 
				{
					include_once($path);
					$is_included_file = true;
					break;
				}			
			}
		}

		if ($strict && !$is_included_file) 
		{
			throw new NICED_NotExistsException('template ' . var_export($template, true).' not exists');
		}
	}
	
	protected function fetch($template, $strict = false) 
	{
		ob_start();
		$this->display($template, $strict);
		return ob_get_clean();
	}
	
	
	protected function import($model_name) 
	{
		return $this->_controller->getModel($model_name);
	}
	
	protected function generateURL($app, $params = array()) 
	{
		require_once DIR_CORE . 'url.php';

		return NICED_URL::generateUrl($app, $params);
	}
	
	protected function _parseTemplateName($name) 
	{
		$name = strtolower($name);

		if(!strpos($name, '.') ) 
		{
			throw new Exception('template name format error，please check');
		} 
		else 
		{
			list($app, $tpl_name) = explode('.', $name, 2);
		}

		return array($app, $tpl_name);
	}
} //end class

abstract class NICED_DataView extends NICED_View 
{
	private $format  = 'json';
	private $charset = 'utf-8';
	private $formats = array('json', 'xml', 'print_r', 'var_dump');

	public function render($content)
	{
		$header = '';
		switch ($this->format)
		{
			case 'json' : 
			{
				$header = 'Content-Type: text/x-json';
				$content = json_encode($content);
			}
			case 'xml' :
			{
				$header = 'Content-Type: text/xml';
			}
			case 'print_r' :
			{	
				print_r($content);
				exit;
			}
			case 'var_dump' :
			{	
				var_dump($content);
				exit;
			}
			default :
			{
			}
		}

		if ($this->charset)
		{
			if ($header)
			{
				$header .= ";charset=".$this->charset;
			}
			else
			{
				$header .= "charset=".$this->charset;
			}
		}
		
		if ($header)
		{
			header($header);
		}
		
		echo $content;
		exit;
	}

	public function setFormat($format = 'json')
	{
		if (!in_array($format, self::$formats)) 
		{
			return false;
		}

		$this->format = $format;

		return true;
	}

	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

} //end class

abstract class NICED_GridView extends NICED_View 
{
	protected $_layout = '';
	protected $_zones = array();
	protected $_zoneConfigs = array();
	
	public function renderLayout() 
	{
		$layout = preg_replace('/[^a-z_0-9]/i', '', $this->_layout);

		if (!$layout) 
		{
			throw new NICED_ValidationException('have no layout!');
		}
	
		$this->render('Default.Grids.' . $layout, true);
	}

	public function setLayout($layout) 
	{
		$this->_layout = $layout;
	}

	public function setZoneConfig($zone_name, array $params) 
	{
		$zone_name = strtolower($zone_name);

		if (!isset($this->_zoneConfigs[$zone_name])) 
		{
			$this->_zoneConfigs[$zone_name] = array();
		}

		foreach ($params as $key => $value) 
		{
			$this->_zoneConfigs[$zone_name][$key] = $value;
		}
	}
	
	public function addZoneContent($zone_name, $tpl_name, $config = array()) 
	{
		$zone_name = strtolower($zone_name);

		if (!isset($this->_zones[$zone_name])) 
		{
			$this->_zones[$zone_name] = array();
		}

		$this->_zones[$zone_name][] = array('name' => strtolower($tpl_name), 'config' => $config);
	}
} //end class