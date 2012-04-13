<?php
class CommonUtil {
	public static function loadTimeParam($prefix = '', $default = "") {
		$time = array();
		$data = array();
		$param_name_start = 'start_time';
		$param_name_stop = 'stop_time';
		if ($prefix) {
			$param_name_start = $prefix.'_start_time';
			$param_name_stop = $prefix.'_stop_time';
		}

		if (isset($_GET[$param_name_start])) {
			$time[$param_name_start] = $_GET[$param_name_start];
		}
		if (!isset($time[$param_name_start]) && isset($_POST[$param_name_start])) {
			$time[$param_name_start] = $_POST[$param_name_start];
		}
		if (isset($_GET[$param_name_stop])) {
			$time[$param_name_stop] = $_GET[$param_name_stop];
		}
		if (!isset($time[$param_name_stop]) && isset($_POST[$param_name_stop])) {
			$time[$param_name_stop] = $_POST[$param_name_stop];
		}
		
		//默认值取当前值

		$data[$param_name_start] = (isset($time[$param_name_start]) && $time[$param_name_start]) ? strtotime(trim($time[$param_name_start])) : $default;
		$data[$param_name_stop] = (isset($time[$param_name_stop]) && $time[$param_name_stop]) ? strtotime(trim($time[$param_name_stop])) : $default;

		if ($data[$param_name_stop]) {
			$data[$param_name_stop] += 60*60*24*1-1;
		}
		
		return $data;
	}

	public static function getUserIp()
	{
		$result = FALSE;
		$fallback=false;
		$fallback_ip_pattern='10.32.';//change this to match whatever IP you preferred (ip office for example)
		//fill the array with candidates IP from various resources
		$ips = isset( $_SERVER['HTTP_X_FORWARDED_FOR'])  ? explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']) : array();
		if( isset( $_SERVER['REMOTE_ADDR'] ) ) $ips[]=$_SERVER['REMOTE_ADDR'];
		if( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) $ips[]=$_SERVER["HTTP_CLIENT_IP"];
		foreach ($ips as $ip)//for all the ips, work on it one by one based on patterns given down here
		{
			if (!preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/",$ip)) continue;//if it doesnt	 match the pattern then skip
			elseif (!preg_match('/^10\.|^127\.|^172\.(?:1[6-9])\.|^224\.|^240\.|^192\.168\./', $ip))//thanks to edy matches all the private ip.
			{

				if (ip2long($ip) != false) //returns false if ip address is invalid
				{
					$result = $ip;
					break;
				}
			}
			elseif(strncmp($ip,$fallback_ip_pattern,strlen($fallback_ip_pattern))===0)//if it starts with the preffered ip this is the fallback
			{

				if (ip2long($ip) != false) //returns false if ip address is invalid
				{
					$fallback=$ip;
				}

			}elseif ($ip == '127.0.0.1')// this is the local ip 
			{
				$result = $ip;
			}

		}
		if ($result===false) $result=$fallback; //if fallback is not found it will be false

		return $result; //if all resources are exhausted and not found, return false.
	}

	public static function curl($url, $param = null, $method = 'post', $options = array(), &$error = null, &$error_code = null, &$info = null) {
		//设置一些参数的默认值
		$timeout = 5;

		if (isset($options['timeout'])) 
		{
			$timeout = $options['timeout'];
		}

		$ch = curl_init();

		if (isset($options['httpheader']))
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $options['httpheader']);
		}

		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		//设置post方式
		if ('post' == strtolower($method)) 
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($param)
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
			}
		} 
		else if ($param) 
		{
			$url .= "?".http_build_query($param);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		$ret  = curl_exec($ch);
		$info = curl_getinfo($ch);

		if (!$ret)
		{
			$error = curl_error($ch);
			$error_code = curl_errno($ch);
		}

		curl_close($ch);

		return $ret;
	}
	
	
	public static function buildHttpQuery($param, $urlencode = false) {
		if ($urlencode) {
			return http_build_query($param);
		} else {
			$query_str = "";
			foreach($param as $key => $value) {
				$query_str .= $key."=".$value."&";
			}

			if ($query_str) {
				$query_str = substr($query_str, 0, strlen($query_str) - 1);

			}
			return $query_str;
		}	
	}

	public static function jsonToArray($json){
		$arr = array();
		if (!$json || (!is_object($json) && !is_array($json))) {
			return $arr;
		}				
		foreach($json as $k => $w){
			if(is_array($w)) {//判断类型是不是object
				$arr[$k] = self::jsonToArray($w); 
			} else if (is_object($w)) {
				$w = (array)$w;
				$arr[$k] = self::jsonToArray($w);
			} else {
				$arr[$k] = $w;
			}
		}

		return $arr;
	}

	public static function addUrlSchema($url) {
		return self::addURLProtocol($url);
	}
	
	//去除url中的协议,http，https
	public static function addURLProtocol($url) {
		if (!$url) {
			return $url;
		}
		if (false === strpos($url, 'http')) {
			$url = "http://".$url;
		} 
		
		return $url;
	}

	public static function writeFile($filename, $content, $type="a+")
	{
		$handle = fopen($filename, $type);
		if($handle)
		{
			fwrite($handle, $content);
			fclose($handle);
			return true;
		}
		else 
		{
			return false;
			error_log('write file fail');
		}
	}

	public static function getUniqueId($type = 1) {
		switch ($type) {
			case 1:return crc32(uniqid(rand()));break;
			default: return uniqid(); 
		}
	}
	
	public static function redirect($new_url, $use301 = true) {
		while(@ob_end_clean());
		if ($use301) {
			 header( "HTTP/1.1 301 Moved Permanently" );
		}
		header('Location: ' . $new_url);
	}


    public static function getDirFiles($dir)
    {
        $files = array();
        if ($handle = opendir($dir)) 
        {

            while (false !== ($file = readdir($handle)))
            {
                if ('.' == $file || '..' == $file)
                {
                    continue;
                }

                $files[] = $file;
            }

            closedir($handle);
        }

        return $files;
    }

	public function is_number($num) 
	{ 
		return  (preg_match("/^[-+]?[0-9]+$/", $num)) ?  TRUE : FALSE; 
	}

	public function abs_crc32_64bit($str)
	{
	   $crc = abs(crc32($str));
	   if( $crc & 0x80000000){
		  $crc ^= 0xffffffff;
		  $crc += 1;
	   }
	   return $crc;
	}

} //end class
