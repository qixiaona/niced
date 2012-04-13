<?php
/**
 *    @usage
 *    $filename = "E:/www/test.csv";
 *	  $rows = Csv::parse($filename, array("Csv", "callback"));
 *	  var_dump($rows);
 *
 */
class Csv
{
	public static function parse($filename, $callback = null)
	{
		if (!file_exists($filename))
		{
			throw new Exception("file not exists!");
		}
		$data = file($filename);
		$rows = array();
		$i = 0;
		foreach ($data as $row) {
			if (0 == $i++) { //the first line is title,pass
				continue;
			}
			$row = explode(',', $row);
			if ($callback)
			{
				$row = call_user_func($callback, $row);
			}
			$rows[] = $row;
		}
		unset($data);

		return $rows;
	}

	public static function callback($row)
	{
		foreach ($row as $k => $v)
		{
			$row[$k] = $v+1;
		}

		return $row;
	}

}//end class