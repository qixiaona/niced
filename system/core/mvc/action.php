<?php
/**
 * @desc acation class file
 * @author nana
 * @date 2011
 *
 */
abstract class NICED_Action 
{
    const INDEX     = 1;

    const INPUT     = 2;

    const ALERT     = 3;

    const SUCCESS   = 4;

    const FAILURE   = 5;

    const DISABLE   = 6;

    const REDIRECT  = 7;

    const CONFIRM   = 8;

    const INVALID   = 9;

    const AUTH      = 10;

    const WARN      = 11;

	const ERROR     = 12;
	
	protected $_controller;
	
	public function __construct(NICED_Controller $c) 
	{
		$this->_controller = $c;
	}

	abstract function execute();//inherit should be define this method
	

	protected function getController() 
	{
		return $this->_controller;
	} 

    public function forward($request_name)
    {
        $action = $this->_controller->instantiate($request_name, 'action');

		if ($action instanceof NICED_Action)
        {
            $result = $action->execute();
			return $result;
        }

		return false;
    }

}
