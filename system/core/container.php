<?php
class NICED_Container {
	
	protected $_data = array();
	public function __call($name, $args) {
		
	}
	
	public function __set($k, $v) {
		
	}
	
	public function __get($k) {
		
	}
	
	public function __isset($k) {
		
	}
	
	public function __unset($k) {
		
	}
	
	public function get($k) {
		
		if(empty($k)) {
			return $this->_data;
		}
		
		$p = str_replace('.', "']['", $k);
		
		return @eval("return \$this->_data['$p'];");
	}
	
	public function set($k, $v) {
		
		$p = str_replace('.', "']['", $k);
		eval ("\$this->_data['$p'] = \$v;");
		
		return $v;
	}

}//end class

class ContainerBase
{

    static function & set(&$v, $name, &$value)
    {
        $b = explode('.', $name);
        $ct = count( $b );
        $end = $ct -1;
        for( $i = 0; $i < $ct; $i++)
        {
            $n = $b[$i];
            if( $i == $end ) return $v[$n] =& $value;
            if( !isset( $v[$n] ) ) 
            {
                $v[$n] = array();
            }
            elseif( $v[$n] instanceof Container ) 
            {
                return $v[$n]->setByRef( implode('.', array_slice($b, $i+1) ), $value );
            }
            elseif( ! is_array($v[$n]) ) 
            {
                $v[$n] = array();
            }
            $v =& $v[$n];
        }
        return $f = FALSE;
    }
    
    function & move( &$v, $target, $destination )
    {
        $r =& ContainerBase::set($v, $destination, ContainerBase::get($v, $target) );
        ContainerBase::remove($v, $target);
        return $r;
    }
    
    static function append( &$v, $name, $value )
    {
        $b = explode('.', $name);
        $ct = count( $b );
        $end = $ct -1;
        for( $i = 0; $i < $ct; $i++)
        {
            $n = $b[$i];
            if( !isset( $v[$n] ) && $i!=$end) 
            {
                $v[$n] = array();
                $v =& $v[$n];
                continue;
            }
            elseif( ! isset($v[$n]) ) 
            {
                $v[$n] = array();
            } 
            elseif( $v[$n] instanceof Container ) 
            {
                return $v[$n]->append( implode('.', array_slice($b, $i+1) ), $value );
            }
            elseif( is_string($v[$n]) ) 
            {
                return $v[$n] .= $value;
            }
            elseif( ! is_array($v[$n]) ) 
            {
                $v[$n] = array();
            } 
            elseif( is_array($v[$n]) && $i!=$end) 
            {
                $v =& $v[$n];
                continue;
            }
            if( $i == $end ) return $v[$n][] =& $value;
            if( empty($v[$n]) ) $v[$n][] = array();
            $max = max( array_keys( $v[$n] ) );
            if( ! is_numeric( $max ) ) $max = 0;
            $v =& $v[$n][$max];
        }
        return FALSE;
    }
    
    static function addNode( &$v, $name, $value )
    {
        $b = explode('.', $name);
        $ct = count( $b );
        $end = $ct -1;
        for( $i = 0; $i < $ct; $i++)
        {
            $n = $b[$i];
            if( !isset( $v[$n] ) ) 
            {
                $v[$n] = array();
            }
            elseif( $v[$n] instanceof Container ) 
            {
                return $v[$n]->append( implode('.', array_slice($b, $i+1) ), $value );
            }
            elseif( is_string($v[$n]) ) 
            {
                return $v[$n] .= $value;
            }
            elseif( ! is_array($v[$n]) ) 
            {
                $v[$n] = array();
            }
            if( $i == $end ) return $v[$n][] =& $value;
            if( empty($v[$n]) ) $v[$n][] = array();
            $max = max( array_keys( $v[$n] ) );
            if( ! is_numeric( $max ) ) $max = 0;
            $v =& $v[$n][$max];
        }
        return FALSE;
    }
        
     static function exists( &$v, $name )
     {
        $b = explode('.', $name);
        $end = count( $b ) - 1;
        for( $i = 0; $i <= $end; $i++)
        {
            $n = $b[$i];
            if( ! array_key_exists( $n, $v) ) return FALSE;
            if( $i == $end ) return TRUE;
            if( $v[$n] instanceof Container ) 
            {
                return $v[$n]->exists( implode('.', array_slice($b, $i+1) ) );
            }
            if( ! is_array($v[$n]) ) return FALSE;
            $v =& $v[$n];
        }
        return FALSE;
    }
       
     static function isEmpty( &$v, $name )
     {
        $value = ContainerBase::get($v, $name);
        return empty( $value );
    }

    static function remove( &$v, $name )
    {
        $b = explode('.', $name);
        $end = count( $b ) -1;
        for( $i = 0; $i <= $end; $i++)
        {
            $n = $b[$i];
            if( !isset( $v[$n] ) ) return NULL;
            if( $i == $end ) 
            {
                $value =& $v[$n];
                unset( $v[$n] );
                return $value;
                
            }
            if( $v[$n] instanceof Container ) 
            {
                return $v[$n]->remove( implode('.', array_slice($b, $i+1) ) );
            }
            if( ! is_array($v[$n]) ) return NULL;
            $v =& $v[$n];
        }
        return NULL;
    }
    
    static function & get( &$v, $name)
    {
        $null = null;
        $b = explode('.', $name);
        $end = count( $b ) -1;
        for( $i = 0; $i <= $end; $i++)
        {
            $n = $b[$i];
            if( !isset( $v[$n] ) ) return $null;
            if( $i == $end ) return $v[$n];
            if( $v[$n] instanceof Container ) 
            {
                return $v[$n]->get( implode('.', array_slice($b, $i+1) ) );
            }
            if( ! is_array($v[$n]) ) return $null;
            $v =& $v[$n];
        }
        return $null;
    }
    

    static function getNames(&$v, $name = "")
    {
        if( strlen( $name )== 0 ) return array_keys($v);
        if( ! ContainerBase::exists($v, $name) ) return array();
        $i = ContainerBase::get($v, $name);
        if ( is_array( $i ) ) return array_keys( $i );
        if( $i instanceof Container ) return $i->getNames();
        return array();
    }
    

    static function size(&$v, $name = "")
    {
        if (strlen($name) == 0) return count($v);
        if( ! ContainerBase::exists($v, $name) ) return FALSE;
        $i = ContainerBase::get($v, $name);
        if ( is_array( $i ) ) return count( $i );
        if( is_numeric( $i ) ) return intval( $i );
        if( is_string( $i ) ) return strlen( $i );
        if( $i instanceof Container ) return $i->size();
        return 0;
    }
    

    static function & getAllData(&$v=null)
    {
        return $v;
    }
}

class Container
{
    protected $_data = array();
    
    function __construct( $data = NULL )
    {
        // copy the data into the container.
        if( is_array( $data ) ) 
            $this->_data = $data;
        
        // if it is a container, pull all of the data into it by copy
        if( $data instanceof Container ) 
            $this->_data = $data->getAllData();
    
    }


    function & set($name, $value)
    {
        $v =& ContainerBase::set($this->_data, $name, $value);
        return $v;
    }
    

    function & setByRef($name, &$value)
    {
        $v =& ContainerBase::set($this->_data, $name, $value);
        return $v;
    }
    

    function append($name, $value)
    {
        return ContainerBase::append($this->_data, $name, $value);
    }
    

    function & move($target, $destination)
    {
        $v =& ContainerBase::move($this->_data, $target, $destination);
        return $v;
    }
    

    function addNode($name, $value)
    {
        return ContainerBase::addNode($this->_data, $name, $value);
    }
    

    function & get($name)
    {
        $v =& ContainerBase::get($this->_data, $name);
        return $v;
    }
    

    function remove($name)
    {
        return ContainerBase::remove($this->_data, $name);
    }
    

    function exists($name)
    {
        return ContainerBase::exists($this->_data, $name);
    }
    
  
     function isEmpty( $name )
     {
        return ContainerBase::isEmpty($this->_data, $name);
     }
    

    function getNames($name = '')
    {
        return ContainerBase::getNames( $this->_data, $name );
    }
    

    function size($name = "")
    {
        return ContainerBase::size( $this->_data, $name );
    }
    

    function & getAllData(&$v=null)
    {
        return $this->_data;
    }
    
}

class SharedContainer extends Container
{
    function __construct( $container )
    {
	if (! $container instanceOf Container ) { 
		throw new Exception("OMG YOU SUXOR! The container you specified for construction is not an instance of the class Container"); }
    	$this->_data =& $container->getAllData();
    }
}

  
class SingletonContainer extends ContainerBase
{
    static function & get( $name, $check_globals = TRUE )
    {
       
       $value =& ContainerBase::get( SingletonContainer::getAllData(), 
                                     $name);
       
       if( $value===NULL && $check_globals )
       {
            $value =& ContainerBase::get( $GLOBALS, $name );
       }
       return $value;
    
    }
    
    static function & set( $name, $value )
    {
        return ContainerBase::set(
            SingletonContainer::getAllData(), 
            $name, 
            $value);
    }
    
    static function & setByRef( $name, & $value )
    {
         return ContainerBase::set(
                SingletonContainer::getAllData(), 
                $name, 
                $value );
    }
    
    static function & setGlobal( $name, $value )
    {
      
        return ContainerBase::set(
            SingletonContainer::getAllData(), 
            $name, 
            ContainerBase::set(
                        $GLOBALS, 
                        $name, 
                        $value));
        
      
    }
    

    static function & setFromGlobal( $name )
    {
        $v =& ContainerBase::get($GLOBALS, $name );
        if( $v===NULL )
        {
            return FALSE;
        }
        return SingletonContainer::setByRef( 
                               $name, 
                               $v );
    }
    

    function & move($target,$destination)
    {
        $v =& ContainerBase::move(SingletonContainer::getAllData(), $target, $destination);
        return $v;
    }
    

	static function append($name, $value)
	{
		return ContainerBase::append(SingletonContainer::getAllData(), $name, $value);
	}
	

	static function appendByRef($name, & $value)
	{
		return ContainerBase::append(SingletonContainer::getAllData(), $name, $value);
	}

    
    static function remove( $name, $clear_from_global = FALSE )
    {

        $result =& ContainerBase::remove(
                        SingletonContainer::getAllData(),
                        $name );
        if( ! $clear_from_global ) return $result;
        
        return ContainerBase::remove(
                        $GLOBALS,
                        $name );
                        
        
    }
    

    static function exists($name, $check_globals = FALSE )
    {
        $result = ContainerBase::exists(
                        SingletonContainer::getAllData(),
                        $name);
        if( $result ) return TRUE;
        if( $check_globals ) 
        {
            return ContainerBase::exists(
                            $GLOBALS,
                            $name );
        }
        return FALSE;
    }
    
  
     static function isEmpty( $name, $check_globals = FALSE )
     {
         $result = ContainerBase::isEmpty(
                        SingletonContainer::getAllData(),
                        $name);
        if( ! $result ) return FALSE;
        if( $check_globals ) 
        {
            return ContainerBase::isEmpty(
                            $GLOBALS,
                            $name );
        }
        return TRUE;
     }
    

    static function getNames( $with_globals = FALSE )
    {
        
        $names = array_keys( SingletonContainer::getAllData() );
        
        if( $with_globals )
        {
            $names = array_merge( $names, array_keys( $GLOBALS ));
        }
        
        return $names;

    }
    

    static function size($name = "", $check_globals = FALSE )
    {
        
        if (strlen($name) == 0) 
        {
            // no size specified
            $size = sizeof(SingletonContainer::getAllData());
            if( $check_globals) $size += sizeof($GLOBALS);
            return $size;
            
        }
        
        $v =& SingletonContainer::get( $name, $check_globals );
        
        return sizeof( $v );
    }
    

    public static function & getAllData(&$v=null)
    {
        
        return self::$vars;
    }
    
  static $vars = array();
    
}

class SC extends SingletonContainer
{

}

class LC extends SingletonContainer
{

}

?>