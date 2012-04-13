<?php
class NICED_Exception extends Exception 
{
	protected $_debug;

	public function __construct($message = null, $debug_info = null) 
	{
		$this->_debug = $debug_info;
		parent::__construct($message);
	}
}

class NICED_ValidationException extends NICED_Exception {}

class NICED_DatabaseException extends NICED_Exception {}

class NICED_NotExistsException extends NICED_Exception {}