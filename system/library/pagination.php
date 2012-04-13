<?php
/**
 * @desc pagination class file
 * @author nana
 * @date 2011
 * @usage
 	function generatePagination($count, $page_size, $page, $url = "", $use_uri=  false) 
	{
		if (!$url && $use_uri) 
		{
			$url = Pagination::getURLQuery();
		}

		$p = new Pagination();
		$p->setUrl($url);
		$p->setTotalRows( $count );
		$p->setPageSize( $page_size );
		$p->setOnPage($page);
		$p->flagPrevNext( true );
		$p->flagFwdBack10( true );
		$p->determinePages();
		$pagination = $p->calculate();

		return $pagination;
	}
 */

class Pagination
{
    protected $base_url;
    protected $page_size;
    protected $page_variable = 'page';
    protected $total_rows;
    protected $total_pages;
    protected $on_page = 1;
    protected $use_sequence = false;
    
    protected $flag_first_last = false;
    protected $flag_prev_next = false;
    protected $flag_fwd_back_100 = false;
    protected $flag_fwd_back_10 = false;
	protected $divider = '...';
    
    protected $showSymbols = array(
                               'prev' 	=> '上一页',
                               'next' 	=> '下一页',
                               'fwd' 	=> '&gt;&gt;',
                               'fwd100' => '&gt;&gt;&gt;',
                               'back100'=> '&lt;&lt;&lt;',
                               'back' 	=> '&lt;&lt;',
                               'first'  => '|&laquo;',
                               'last' 	=> '&raquo;|',
                               'divider'=> ' ',
								);
    
    public function generateForum($url, $num_items, $page_size, $start, $add_prevnext_text = true) 
	{
        $this->setUrl($url);
        $this->setTotalRows($num_items);
        $this->setPageSize($page_size);
        $this->setOnPage($current_num);
        $this->flagPrevNext($add_prevnext_text);
        $this->determinePages();

        return $this->calculate();
    }
	
    public function setUrl($url) 
	{
        return $this->base_url = $this->validateUrl($url);
    }
	
    public function setTotalRows($total_rows) 
	{
        return $this->total_rows = intval($total_rows);
    }
	
    public function setPageSize($page_size) 
	{
        return $this->page_size = intval($page_size);
    }
	
	public function setPageVariable($page_variable = 'page') 
	{
		return $this->page_variable = trim(strval($page_variable));
	}
	
    public function setOnPage($current_page) 
	{
        if ( isset($current_page)) 
		{
			return $this->on_page = intval($current_page);
		}

        return false;
    }
    
    public function flagFirstLast($flag = true) 
	{
        return $this->flag_first_last = ($flag == true) ? true : false;
    }
	
    public function flagPrevNext( $flag = true ) 
	{
        return $this->flag_prev_next = ($flag == true) ? true : false;
    }
	
    public function flagFwdBack100($flag = true)
	{
        return $this->flag_fwd_back_100 = ($flag == true) ? true : false;
    }
	
    public function flagFwdBack10( $flag = true) 
	{
        return $this->flag_fwd_back_10 = ($flag == true) ? true : false;
    }
    
    public function useSequenceLinks()
	{
        $this->use_sequence = true;
    }
    
    public function usePageLinks()
	{
        $this->use_sequence = false;
    }
    
    public function setDivider($v)
	{
        $this->showSymbols['divider'] = $v;
    }
    
    public function setFirstLast($first, $last)
	{
        $this->showSymbols['first'] = $first;
        $this->showSymbols['last']  = $last;
    }
    
    public function setPrevNext($prev, $next)
	{
        $this->showSymbols['prev'] = $first;
        $this->showSymbols['next'] = $next;
    }
    
    public function setForwardBack($fwd, $back)
	{
        $this->showSymbols['fwd']  = $fwd;
        $this->showSymbols['back'] = $back;    
    }
   
    public function determinePages() 
	{
        if( $this->total_rows < 1 ) 
		{
			return $this->total_pages = 0;
		}

        if(  $this->page_size < 1 )  
		{
			$this->page_size = 1;
		}

        $temp_pages = ($this->total_rows / $this->page_size);
        $this->total_pages = ($temp_pages == floor($temp_pages)) ? $temp_pages : floor($temp_pages) + 1;

        return $this->total_pages;
    }

    public function calculate()
	{
		$html = "";
        $this->determinePages();

        if( $this->on_page > $this->total_pages ) 
		{
			return $html;
		}
  
        $pages = $this->calculatePages();
        if( ! is_array( $pages ) ) 
		{
			return $html;
		}
        
        foreach(array('next', 'next10', 'next100', 'last') as $method)
		{
            $check = 'show' . $method;
            if($this->$check()) 
			{
				array_push( $pages, $this->$method());
			}
        }
        
        foreach(array('prev', 'prev10', 'prev100', 'first') as $method)
		{
            $check = 'show' . $method;
            if($this->$check()) 
			{
				array_unshift($pages, $this->$method());
			}
        }

        return implode($this->showSymbols['divider'], $pages);
    }
    
    
    private function calculatePages() 
	{
        $pages = array();
       
        if ($this->total_pages > 20)
        {
			$init_page_max = ($this->on_page < 2) ? 6 : 2;
			
			for($i = 1; $i < $init_page_max + 1; $i++)
			{
				$pages[] = $this->pageLink($i);
			}	

			if ($this->on_page > 1  && $this->on_page < $this->total_pages)
			{
				$pages[] = ($this->on_page > 5) ? '...' : $this->divider;
						
				$init_page_min = ($this->on_page > 4) ? $this->on_page : 5;
				$init_page_max = ($this->on_page < $this->total_pages - 4) ? $this->on_page : $this->total_pages - 4;
						
				if ($init_page_max + 6 >= $this->total_pages - 2) 
				{
					$mid_page_index = $this->total_pages - 2;
				}
				else 
				{
					$mid_page_index = $init_page_max + 6;
				}

				for($i = $init_page_min - 2; $i < $mid_page_index; $i++)
				{
					$pages[] = $this->pageLink($i);
				}
				
				$pages[] = ($this->on_page < ($this->total_pages - 3)) ? '...' : $this->divider;
			}
			else
			{
				$pages[] = '...';
			}
			
			for($i = $this->total_pages - 2; $i < $this->total_pages + 1; $i++)
			{
				$pages[] = $this->pageLink($i);
			}
		} 
		else if ($this->total_pages > 10)
		{
			$init_page_max = ($this->total_pages > 3) ? 3 : $this->total_pages;
					
			for($i = 1; $i < $init_page_max + 1; $i++)
			{
				$pages[] = $this->pageLink($i);
			}
			
			if ($this->total_pages > 3)
			{
				if ($this->on_page > 1 && $this->on_page < $this->total_pages)
				{
					$pages[] = ($this->on_page > 5) ? '...' : $this->divider;
					
					$init_page_min = ($this->on_page > 4) ? $this->on_page : 5;
					$init_page_max = ($this->on_page < $this->total_pages - 4) ? $this->on_page : $this->total_pages - 4;
		
						
					for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++)
					{
						$pages[] = $this->pageLink($i);
					}
	
					$pages[] = ($this->on_page < $this->total_pages - 4) ? '...' : $this->divider;
				}
				else
				{
					$pages[] = '...';
				}
		
				for($i = $this->total_pages - 2; $i < $this->total_pages + 1; $i++)
				{
					$pages[] = $this->pageLink($i);
				}
			}
		}
		else
		{
			for($i = 1; $i < $this->total_pages + 1; $i++)
			{
				$pages[] = $this->pageLink($i);
			}
		}
				
		return $pages;
	}
	

	private function pageLink($page_num, $symbol = null, $title = null)
	{
		$html = "";
	    if(strlen( $page_num) < 1) 
		{
			return $html;
		}

	    if($title === null) 
		{
			$title = $page_num . '页' ;
		}

		$class = '';

	    if($symbol === null) 
		{
	        $symbol = $page_num;
			$class = ' class=" xpage" ';
	    } 
		else 
		{
	        $class = ' class="page_jump xpage" ';
	    }
	    
	    if ($page_num == $this->on_page) 
		{
		    $class= ' class="page_current xpage" ';
		}
			
        return '<a p='.$this->calcPage($page_num).' href="'.$this->base_url.$this->calcPage( $page_num ).'"  title="'.$title.'"'.$class.'>'.$symbol.'</a>';
	}
	
    protected function first()
	{
        return $this->pageLink(1, $this->showSymbols['first'], '第一页');
    }
    
    protected function last()
	{
        return $this->pageLink($this->total_pages, $this->showSymbols['last'], '最后一页');
    }    
    
	protected function prev()
	{
        return $this->pageLink($this->on_page-1,  $this->showSymbols['prev'], '前一页');
    }
    
    protected function next()
	{
		return $this->pageLink($this->on_page + 1 , $this->showSymbols['next'], '下一页');
	}
    
    protected function prev10()
	{
        return $this->pageLink($this->on_page - 10,  $this->showSymbols['back'] , '回退10页');
    }
    
    protected function next10(){
        return $this->pageLink($this->on_page + 10, $this->showSymbols['fwd'], '前进10页');
    }
    
    protected function prev100() 
	{	
        return $this->pageLink($this->on_page - 100, $this->showSymbols['back100'], '回退100页');
    }
    
	protected function next100()
	{
        return $this->pageLink($this->on_page + 100, $this->showSymbols['fwd100'], '前进100页');
    }
    
    protected function showFirst()
	{
        return ( $this->flag_first_last && $this->on_page > 1 && $this->total_pages > 100 ) ? true : false;
    }
    
    protected function showLast()
	{
        return ( $this->flag_first_last && $this->on_page < $this->total_pages && $this->total_pages > 100 ) ? true : false;
    }
    
    protected function showPrev()
	{
        return (  $this->flag_prev_next && $this->on_page > 1 ) ? true : false;
    }
    
    protected function showNext()
	{
	    return ($this->flag_prev_next && $this->on_page < $this->total_pages ) ? true : false;
	}
    
    protected function showPrev10()
	{
        return ($this->flag_fwd_back_10 && $this->total_pages > 20 && $this->on_page > 10) ? true : false;
    }
    
    protected function  showNext10()
	{
        return ($this->flag_fwd_back_10 && $this->total_pages > 20  && $this->on_page < ($this->total_pages - 10)) ? true : false;
    }
    
    protected function showPrev100()
	{
        return ($this->flag_fwd_back_100 && $this->total_pages > 100 && $this->on_page > 100) ? true : false;
    }

    protected function showNext100()
	{
        return ($this->flag_fwd_back_100 && $this->total_pages > 100 && $this->on_page < ($this->total_pages - 100)) ? true : false;
    }
    
	
	private function calcPage($v)
	{
	    if(!$this->use_sequence) 
		{
			return $v;
		}

	    return ($v * $this->page_size) - $this->page_size + 1;
	}


    protected function validateUrl($in_url) 
	{
		$in_url .= preg_match('/\?/i', $in_url)
            ? '&'.$this->page_variable.'='
            : '?'.$this->page_variable.'=';
			
        return $in_url;
    }

	public static function getURLQuery() 
	{
		$url       = $_SERVER["REQUEST_URI"];
		$parse_url = parse_url($url);
		$url_query = isset($parse_url["query"]) ? $parse_url["query"] : null;
		
		if($url_query) 
		{
			$page = isset($_GET['page']) ? $_GET['page'] : "";
			$url_query = preg_replace("/(^|&)page=$page/", "", $url_query);
			$url = str_replace($parse_url["query"], $url_query, $url);	
		} 

		return $url;
	}

	public static function stripURLParam($url, $key, $value)
	{
		$url = preg_replace("/(^|&)$key=$value/", "", $url);
		
		return $url;
	}

    public static function addStrToURL($in_url, $str) 
	{
		$in_url .= preg_match('/\?/i', $in_url)
            ? '&'.$str
            : '?'.$str;
			
        return $in_url;
    }
} //end class
