<?php
class Sample_Index_Model extends NICED_Model
{
	public function hello()
	{
		$message = 'hello, this is sample index page';
		$this->set('message', $message);

		return $message;
	}

}//end class
