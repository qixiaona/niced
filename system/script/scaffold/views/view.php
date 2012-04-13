<?php
class {$app}_{$action}_View extends NICED_View 
{
	public function execute() 
	{
		$controller = $this->getController();
		$exception  = $controller->getException();
		$output     = array();

		if ($exception && $exception instanceof Exception)
		{
			$output['message'] = $exception->getMessage();
		}

		$model   = $this->import('{$app}.{$action}');
		$message = $model->get('message');

		$this->set('output', $output);
		$this->set('message', $message);

		$this->render('{$app}.{$action}');
	}

}//end class
