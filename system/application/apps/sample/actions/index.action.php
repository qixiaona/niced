<?php
class Sample_Index_Action extends NICED_Action 
{       
	public function execute() 
	{
		$controller = $this->getController();
		$request    = $controller->getRequest();		
		
		$model = $controller->getModel('Sample.Index');

		try
		{
			$message = $model->hello();
		}
		catch (Exception $e)
		{
			$controller->handleException(new NICED_Exception($e->getMessage()));
			return self::SUCCESS;
		}

		return self::SUCCESS;
	}

}//end class
