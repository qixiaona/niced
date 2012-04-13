<?php
/**
 * @desc model class file
 * @author nana
 * @date 2011
 *
 */
class NICED_Model extends Container 
{
	protected $_controller;
	
	public function __construct(NICED_Controller $c) 
	{
		$this->_controller = $c;
	}	
} //end class
