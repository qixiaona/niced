<?php
/**
  * @uage:

        $dao = NICED_DaoFactory::create('test.test', 'select');
        $dao->setWhere('name="nana"');
        $dao->byId(3, true);
        $dao->setName('nana');
        $dao->setId(5);
        $rs = $dao->execute();
        $rows = $rs->fetchAll();
        var_dump($dao->getSql()->render());


        $sql = "select * from test limit 1";
        $rs = $dao->execute($sql);


        var_dump($db->query($sql));
  * @desc dao class file
  * @author nana
  * @date 2011
  *
  */
require_once(DIR_CORE.'sql.php');
class NICED_Dao 
{
	protected $db;
	protected $sql;
	protected $exec_handler;
	public    $dsn;
	public    $table;
	protected $tableName;
	
	public function __construct() 
	{

	}

	public function db($dsn = null) 
	{
        if (!$dsn)
        {
            $dsn = $this->getDsn();
        }
        if (!$dsn)
        {
            throw new Exception("没有dsn");
        }
		$this->db = NICED_DBFactory::create($dsn);

		return $this->db;
	}

	public function getDsn() 
	{
		return $this->dsn;
	}

	public function getTableName()
	{
		return $this->table;
	}

	public function execute($sql = null) 
	{
		if (!$sql)
		{
			$sql = $this->getSql()->render();
		}

		return $this->getExecHandler()->performQuery($this, $this->dsn, $sql);
	}

	public function performQuery($dao, $dsn, $sql) 
	{
		$db = $dao->db();
		$rst = $db->query($sql);

		return $this->setRs($rst);
	}


	public function setRS($rs) 
	{ 
		if(method_exists($this, 'formatrow')) 
		{
			$rs->addRowManipulator(array($this, 'formatRow'));
		}

		return $rs;
	}

	public function getExecHandler() 
	{
		return ($this->exec_handler) ? $this->exec_handler : $this;
	}

	public function setExecHandler($dao) 
	{
		return $this->exec_handler = $dao;
	}

    public function attach($dao)
    {
        $dao->setExecHandler($this);

        return $dao;
    }

	public function setSql($sql)
	{
		$this->sql = $sql;
	}

	public function getSql()
	{
		$this->sql->setFrom($this->getTableName());

		return $this->sql;
	}
	
} //end class

class NICED_DynamicDao extends NICED_Dao 
{
	protected $tablePrefix;

    public function __construct($name, $config = null, $type = 'select')
    {
		$this->resolveConfig($name, $config);

	    $sql = new SQL(str_replace('sql', '', strtolower($type)), $this->getTableName());
        $this->setSql($sql);

        parent::__construct();
    }

	public function resolveConfig($name, $config) 
	{
		if (!isset($config['dao']))
        {
            throw new Exception("请指定dao");
        }

        $dao_config = $config['dao'];

        if (!$dao_config)
        {
            throw new Exception("请指定dao");
        }

        $this->dsn = isset($dao_config['dsn']) ? $dao_config['dsn'] : null;

        if (!$this->dsn) 
		{
            throw new NICED_NotExistsException('DAO '.$name.' not found dsn', $this);
        }

        $this->table = isset($dao_config['table']) ? $dao_config['table'] : null;
		$this->tableName = $this->table;

        if (!$this->tableName) 
		{
            throw new NICED_NotExistsException('DAO '.$name.' table未指定', $this);
        }
	}

	public function __call($method, $args)
	{
		$method = strtolower($method);
        list($cmd, $needle) = $this->_resolveCall($method);

        return $this->$cmd($needle, $args);		
	}

    protected function _resolveCall($method)
    {
        static $prefixes = array('set', 'get');//, 'increment', 'addto', 'by', 'compare'

        foreach($prefixes as $prefix)
        {
            if( substr( $method, 0, strlen($prefix) ) == $prefix ) return array('_dynamic' . $prefix, substr( $method, strlen($prefix) ) );
        }

        throw new Exception('invalid method call '.$method );
    }

	public function set($field, $v, $type = null)
	{
		$this->getSql()->set($field, $v, $type);
	}

    protected function _dynamicSet($needle, $args)
	{
        return $this->set($needle, (isset( $args[0] ) ?  $args[0] : NULL));
    }

	protected function get($n)
	{ 
		return $this->getSql()->get($n);
	}

    protected function _dynamicGet($needle)
    {
        /**
		$needle = str_replace('_', '', strtolower($needle));
        foreach( $this->fields() as $field=>$type)
        {
            $match = str_replace('_', '', strtolower($field));
            if( $needle == $match )  return $this->get($field);
        }**/
        throw newException('get not supported for: ' . $needle );
    }

	public function byId($v, $use_append = FALSE) 
	{ 
		return $this->_byField('id', $v, $use_append); 
	}

	public function _byField($field, $v, $use_append = FALSE)
    {
        //$fields = $this->fields();
        //$type = $fields[$field];
        $method = ($use_append) ? 'appendWhere' : 'setWhere';

		//generate eg. "item_definition.type"
        if (isset($this->tablePrefix) && $this->tablePrefix) 
		{
            $field = $this->tablePrefix.'.'.$field;
        }

		$clean_ids = $this->cleanIds($v);

		if( count($clean_ids) < 1 ) 
		{
			throw new Exception('no valid ids in list');
		}

		if( count( $clean_ids ) == 1 ) 
		{
			return $this->$method(sprintf("%s = %s", $field, $clean_ids[0]));
		}

		return $this->$method(sprintf('%s IN ( %s )', $field, implode(', ', $clean_ids)));
    }

    public function cleanIds($ids)
    {
        if(is_scalar($ids)) 
		{
			$ids = explode(',', $ids);
		}

        if(!is_array($ids) || sizeof($ids) < 1) 
		{
			return array();
		}

        $clean_ids = array();

        foreach($ids as $id)
        {
            if( ! is_scalar( $id ) ) continue;
            if( $id != 0 ) $id = ltrim($id, '0 ');
            if( preg_match("/^[-+]?[0-9]+$/", $id) && !in_array($id, $clean_ids)) $clean_ids[] = $id;
        }

        sort($clean_ids);

        return $clean_ids;
    }

    public function setWhere($v)
    {
        return $this->_setSQLParam('setWhere', $v);
    }

    public function appendWhere($v)
    {
        return $this->_setSQLParam('appendWhere', $v);
    }

    public function setOrder($v)
    {
        return $this->_setSQLParam('setOrder', $v);
    }

    public function appendOrder($v)
    {
        return $this->_setSQLParam('appendOrder', $v);
    }

    public function setGroup($v){ 
		return $this->_setSQLParam('setGroup', $v); 
	}

    public function appendGroup($v){ 
		return $this->_setSQLParam('appendGroup', $v); 
	}

	public function setLimit($v){ 
		return $this->_setSQLParam('setLimit', $v);
	}

	public function _setSQLParam($method, $attribute){ 
		return $this->getSql()->$method($attribute); 
	}

	public static function resolveCondition($data) 
	{
		$condition = null;
		$conditions = array();
		foreach ($data as $field => $value) 
		{
			switch ($field) 
			{
				case 'time' : 
				{
					foreach ($value as $k => $v) 
					{
						$field = $k;
						$start_time = isset($v['start']) ? $v['start'] : null;
						$stop_time = isset($v['stop']) ? $v['stop'] : null;
						
						if ($start_time) 
						{				
							if ($stop_time) 
							{
								$conditions[] = $field." BETWEEN '".$start_time."' AND '".$stop_time ."'";
							} 
							else 
							{
								$conditions[] = $field." >= '".$start_time."'";
							}
						}
					}
					break;
				}
				default: 
				{
					switch (gettype($value)) 
					{
						case 'array' : 
						{
							$conditions[] = $field." IN ("."".implode(',', $value).")";
							break;
						}
						default : 
						{
							$conditions[] = $field."="."'".$value."'";
						}
					}
				}
			}
			
		}
		
		$condition = implode(' AND ', $conditions);

		return $condition;
	}

} //end class

class NICED_DaoFactory 
{
	public static $setting;

	public static function setDaoConfig($config) 
	{
		self::$setting = new Container();
		self::$setting->set('dao', $config);
	}

    public static function create($name, $type = 'select') 
	{
		if (!$name) 
		{
			throw new NICED_ValidationException("请指定dao名称");
		}

		$config = array();
		//$config['dao'] = SC::get('board_config.dao.'.$name);
		$config['dao'] = self::$setting->get('dao.'.$name);

        if (!$config['dao'])
        {
            throw new Exception("找不到指定的dao");
        }

		return new NICED_DynamicDao($name, $config, $type);
    }
   
    
    public static function construct($name, $type = 'select') 
	{
        return NICED_DaoFactory::create($name, $type);
    }
}


class NICED_TransactionManager extends NICED_Dao 
{

    protected $connectors = array();

	public function __construct()
	{
	}

    public function performQuery($dao, $dsn, $sql) 
	{
        $db = $this->db($dsn);
		$rst = $db->query($sql);

		$rst = $this->setRS($rst);
		
		$rows = $dao->setRS($rst); 
		
        return  $rows;
    }
     
    public function db($dsn = null) 
	{
		if (!$dsn) 
		{
			throw new NICED_ValidationException("请指定dsn！");
		}

        if (isset($this->connectors[$dsn])) 
		{
			return $this->connectors[$dsn];
		}

		$db = NICED_DBFactory::create($dsn . '.transaction' . mt_rand(0, 100000));

        $tran = $db->createTransaction();
        $this->connectors[$dsn] = $tran;
        $tran->begin();

        return $this->connectors[$dsn];
    
    }
   
   public  function getExecHandler() 
	{
        return $this;
    }
    
    public function setRS($rs) 
	{
        if(!$rs->isSuccess()) 
		{
			$this->rollback();
		}

		$rs = parent::setRS($rs);
		
        return $rs;
    }
    
    public function rollback() 
	{
        if(empty($this->connectors)) 
		{
			return false;
		}

        $action = 'rollback';

        foreach(array_keys($this->connectors) as $k) 
		{
            $this->connectors[$k]->rollback();
        }
        $this->resetConnectors();

        return true;
    }
    
    public function inProgress() 
	{
    	return (empty($this->connectors)) ? FALSE : TRUE;
    }
    
    public function commit() 
	{
        if (!$this->inProgress()) 
		{
        	return false;
        }

        $action = 'commit';
        $status = false;

        foreach(array_keys($this->connectors)  as $k)
        {
            $status = $this->connectors[$k]->$action();
        }
        $this->resetConnectors();

        return $status;
    }

    public  function & connectors()
	{ 
	   return $this->connectors; 
    }

    public function resetConnectors()
    {
        if(sizeof($this->connectors ) > 0) 
		{
            foreach(array_keys($this->connectors) as $k) 
			{
				if (method_exists($this->connectors[$k], 'close')) 
				{
					$this->connectors[$k]->close();
				}
            }
        }
        $this->connectors = array();
    }
    
    public  function __destruct()
	{
        if( $this->inProgress() ) 
		{
			$this->rollback();
		}
    }

} //end class
