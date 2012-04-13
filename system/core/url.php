<?php
/**
 * @desc rul class file
 * @author nana
 * @date 2011
 *
 */
class NICED_URL 
{
	public static function generateURL($app, $params = array(), $protocol = false) 
	{
		$routers = LC::get('router');
		$path = array_search(strtolower($app), $routers);

		if (!$path) 
		{
			throw new NICED_Exception('can not find path: '.$path, $app);
		}
		
		$query = trim($path);
		$p = array();

		foreach ($params as $key => $value) 
		{
			if (is_int($key)) 
			{
				$query .= '/' . $value;
			} 
			else 
			{
				$p[] = urlencode($key) . '=' . urlencode($value);
			}
		}

		if (count($p)) 
		{
			$query .= '?' . implode('&', $p);
		}

		$url = rtrim(self::getBaseURL($protocol), '/')."/".$query;
        
        return $url;
	}

	//返回base url地址
	public static function getBaseURL($protocol = false, $add_index_page = true)
	{
		$site_path  = SC::get('board_config.path_site');
		$index_page = SC::get('board_config.index_page_name');

        $base_url = "";
        // add index.php
		if ($add_index_page)
		{
			$base_url = trim(trim($site_path, "/")."/".trim($index_page, "/"), '/');
		}
		else
		{
            $base_url = trim($site_path, '/');
		}

        if (!$base_url)
        {
            return $base_url;
        }

        //protocol
		if ($protocol)
		{
			$base_url = rtrim(MAIN_DOMAIN, '/')."/".$base_url;
		}
		else
		{
			$base_url = "/".$base_url;
		}

		return $base_url;
	}

	public static function getPublicHtmlURL($protocol = false)
	{
		$site_path  = SC::get('board_config.path_site');
		$path       = SC::get('board_config.path_public_html');
        $url        = trim(trim($site_path, '/').'/'.trim($path, '/'), '/');
      
        if ($protocol)
        {
            $url = trim($domain, '/').'/'.trim($url, "/");
        }
        else if ($url)
        {
            $url = '/'.$url;
        }

        return $url;
	}

	public static function getStaticURL($protocol = false)
	{
		$site_path  = SC::get('board_config.path_site');
		$path       = SC::get('board_config.path_static');
        $url        = trim(trim($site_path, '/').'/'.trim($path, '/'), '/');
      
        if ($protocol)
        {
            $url = trim($domain, '/').'/'.trim($url, "/");
        }
        else if ($url)
        {
            $url = '/'.$url;
        }

        return $url;		
	}
    
    public static function getStaticBaseURL($type, $protocol = false)
    {
        $site_path  = SC::get('board_config.path_site');
        
        switch($type)
        {
            case 'css' :
            {
                $path = SC::get('board_config.path_css');
                $domain = CSS_DOMAIN;
                break;
            }
            case 'js' : 
            {
                $path = SC::get('board_config.path_js');
                $domain = JS_DOMAIN;
                break;
            }
            case 'image' : 
            {
                $path = SC::get('board_config.path_image');
                $domain = IMAGE_DOMAIN;
                break;
            }
            default : 
            {
                throw new Exception('获取static base url type error, not valid');
            }
        }

        $url        = trim(trim($site_path, '/').'/'.trim($path, '/'), '/');
        
        if ($protocol)
        {
            $url = trim($domain, '/').'/'.trim($url, "/");
        }
        else if ($url)
        {
            $url = '/'.$url;
        }

        return $url;
    }


    public static function getJsBaseURL($protocol = false)
    {
        return self::getStaticBaseURL('js', $protocol);
    }

    
    public static function getCssBaseURL($protocol = false)
    {
        return self::getStaticBaseURL('css', $protocol);
    }

    public static function getImageBaseURL($protocol = false)
    {
        return self::getStaticBaseURL('image', $protocol);
    }




}//end class