<?php
/**
 * @desc db class file
 * @author nana
 * @date 2011
 *
 */
class NICED_DBFactory 
{

	public static    $dsn_settings  = array();
	public static    $config        = array();
	protected static $_connections  = array();
	public static    $populated     = false;
	
	private function __construct() 
	{
	}

	//将配置文件中的dsn读取到类中
	public static function setConfig($type, $settiongs) 
	{
		self::$config[$type] = $settiongs;
	}

	public static function getDsnSettings($dsn) 
	{
		return isset(self::$dsn_settings[$dsn]) ? self::$dsn_settings[$dsn] : array();
	}

	//resolve dsn and server config to dsn_settings
	public static function resolve() 
	{
		if (self::$populated) 
		{
			return true;
		}

		if (!isset(self::$config['dsn']) || !isset(self::$config['server']))
		{
			return false;
		}

		foreach (self::$config['dsn'] as $dsn => $cfg)
		{
			if ('mysql' == $cfg['type'])
			{ //如果是mysql，将server中的信息合并到dsn中
				$cfg = array_merge($cfg, self::$config['server'][$cfg['server_id']]);
			}
			self::$dsn_settings[$dsn] = $cfg;
		}

		self::$populated = true;

		return true;
	}
	
	/**
	 * @desc create dao object
	 * @param $dsn
	 * @return NICED_PDO
	 */
    public static function create($dsn = null) 
	{
    	if (isset(self::$_connections[$dsn])) 
		{
    		return self::$_connections[$dsn];
    	}
    	try
		{
    		//$connect = new NICED_MySQLi($ip, $user, $pass, $dbname, $port);
			if (!class_exists('NICED_MySQLi'))
			{
				throw new Exception('NICED_MySQLi class not exists');
			}

			$link = new NICED_MySQLi($dsn);
    	} 
		catch (Exception $e) 
		{
    		throw new NICED_DatabaseException('db error: '.$e->getMessage(), $dbconfig);
    	}
    	self::$_connections[$dsn] = $link;

    	return $link;
    }

	public static function getDsnSetting($dsn) 
	{
		if (!$dsn) 
		{
			$dsn = 'default';
		} 
		else 
		{
			$vars = explode('.', $dsn);
			$dsn = $vars[0];
		}

		$dsn_settings = &self::$dsn_settings;
    	$db_setting = $dsn_settings[$dsn];
		
		return $db_setting;
	}

	public static function closeAll() 
	{
		$connectors = &self::$_connections;

        if(empty($connectors)) 
		{
			return;	
		}
		
        foreach(array_keys($connectors) as $key)
        {
            if(is_object($connectors[$key]) && method_exists( $connectors[$key], 'close')) $connectors[$key]->close();
        }
	}


}

register_shutdown_function(array('NICED_DBFactory', 'closeAll'));

if (class_exists('PDO'))
{
	class NICED_PDO extends PDO 
	{
		
	}
}

if (class_exists('MySQLi'))
{
	class NICED_MySQLi extends MySQLi
	{
		protected $connected = false;
		protected $dsn;
		protected $sqlStatement;
		protected $queries = array();

		protected $debug = true;
		protected $queryCount = 0;
		protected $maxQueryLogCount = 20;
		protected $lastQueryTime;

		public function getConnect() 
		{
			$db_setting = NICED_DBFactory::getDsnSetting($this->dsn);
			$type   = $db_setting['type'];
			$ip     = $db_setting['ip'];
			$port   = $db_setting['port'];
			$dbname = $db_setting['dbname'];
			$user   = $db_setting['user'];
			$pass   = $db_setting['pass'];
			$dsn    = $type.':host='.$ip.';dbname='.$dbname.';port='.$port;
			
			try
			{
				$mysqli = parent::__construct($ip, $user, $pass, $dbname, $port);
				$this->set_charset("utf8");
				if ($this->connect_error)
				{
					throw new Exception("数据库连接错误！".$this->connect_error);
				}
			} catch (Exception $e) {
				throw new NICED_DatabaseException($e->getMessage());
			}
			$this->connected = true;

			return $this;
		}

		public function __construct($dsn) 
		{
			//parent::__construct();
			$this->dsn = $dsn;
		}

		public function isConnected() 
		{
			return $this->connected;
		}


		public function query($sql) 
		{
			$this->sqlStatement = $sql;
			$start = microtime(TRUE);

			if (!$this->isConnected()) 
			{
				$this->getConnect();
				if (!$this->isConnected())
				{
					throw new NICED_DatabaseException("数据库连接失败！");
				}
			}
			
			$rst = parent::query($sql);
			$end = microtime(TRUE);
			$this->lastQueryTime = time();

			if (!$rst) 
			{
				throw new NICED_DatabaseException("数据库操作失败".$this->error);
			}
			$result = new NICED_Result($this, $rst, $sql);

			if($this->debug) $this->logQuery($result, $start, $end);

			return $result;
		}

		public function logQuery($rs, $start = NULL, $end = NULL)
		{
			if(!$this->debug) 
			{
				return;
			}
			$this->queryCounter++;

			$this->queries[] = array('type'=>'query', 'ct'=>$this->queryCounter, 'rs'=>$rs, 'start'=>$start, 'end'=>$end, 'backtrace'=> debug_backtrace());

			if($this->queryCount > $this->maxQueryLogCount ) 
			{
				$this->debug = FALSE;
			}
		}

		public function createTransaction()
		{
			return new MysqlTransaction($this);
		}
	}
}

class MysqlTransaction extends Transaction
{
    // @see CommonTransaction
    function MysqlTransaction(&$database)
    {
        parent::Transaction($database);
    }
    
    // @see CommonTransaction
    function begin()
    {
         $result             = $this->database->query('BEGIN WORK');
         $this->valid        = $result->isSuccess();
         $this->errorMessage = $result->errormessage();
         $this->errorCode    = $result->errorcode();
         return $this->valid;
    }
    
    // @see CommonTransaction
    function commit()
    {
        return $this->end('COMMIT');
    }
    
    // @see CommonTransaction
    function rollback()
    {
        return $this->end('ROLLBACK');
    }

    function escape($s){ return mysqldb::escape($s);}
    function escapemethod() { return mysqldb::escapemethod(); }    
    function sql_escape($s){ return mysqldb::escape($s);}
    function sql_escapemethod() { return mysqldb::escapemethod(); }
}

class Transaction
{
    // DATA MEMBERS
    
    /***
     * The database this transaction runs on
     * @type Database
     ***/
    var $database;
    
    /***
     * Whether this transaction is valid
     * @type bool
     ***/
    var $valid;
    
    /***
     * The error message produced by the database when this transaction became
     * invalid, or the empty string if all is well
     * @type string
     ***/
    var $errorMessage;
    
     /***
     * The error message produced by the database when this transaction became
     * invalid, or the empty string if all is well
     * @type string
     ***/
    var $errorCode;

    // CREATORS
    
    /***
     * Create a new transaction
     * @param $database the database connection to create the transaction for.
     ***/
    function Transaction(&$database)
    {
        $this->database     =& $database;
        $this->valid        =  false;
        $this->errorMessage =  '';
    }
    
    function dsn()
    {
        return $this->database->dsn();
    }
    
    function useSlave( $v = true ){
        return $this->database->useSlave( $v );
    }
    
    function usingSlave(){
        return $this->database->usingSlave();
    }
    
    // MANIPULATORS
    
    /***
     * Begin this transaction; returns <code>true</code> if the transaction
     * could be started, and <code>false</code> otherwise.
     * @returns bool
     ***/
    function begin()
    {
         return false;
    }
    
    /***
     * End this transaction, either by a commit or a rollback. If the 
     * transaction was ended succesfully, <code>true</code> is returned, and
     * <code>false</code> otherwise.
     * @param $sql the SQL query to end the transaction with
     * @returns bool
     * @private
     ***/
    function end($sql)
    {
        if (!$this->valid)
        {
            return false;
        }
        $this->valid = false;
        if ($sql !== '')
        {
            $result =& $this->database->query($sql);
            return $result->isSuccess();
        }
        return true;
    }

    /***
     * Execute a query in this transaction and return the result. If this
     * transaction is invalid, the query isn't executed and <code>false</code>
     * is returned.
     * @param $sql the query to execute
     * @returns QueryResult
     ***/
    function query($sql)
    {
        if (!$this->valid)
        {
            return $this->database->query(null);
        }
        $result =& $this->database->query($sql);
        if ( !$result->isSuccess() )
        {
            $this->setError($result->errormessage(), $result->errorcode());
            $this->rollback();
        }
        return $result;
    }
    
    
    /***
     * Commit the transaction; returns <code>true</code> on succes, and
     * <code>false</code> otherwise.
     * @returns bool
     ***/
    function commit()
    {
        return false;
    }
    
    /***
     * Roll back the transaction; returns <code>true</code> on succes, and
     * <code>false</code> otherwise.
     * @returns bool
     ***/
    function rollback()
    {
        return false;
    }
    
    // ACCESSORS

    
    /***
     * Check if the transaction is valid; a transaction is invalid if 
     * <code>begin</code> hasn't been called on it successfully or if any query
     * inside the transaction fails to execute successfully.
     * @returns bool
     ***/
    function isValid()
    {
        return $this->valid;
    }
    
    function isSuccess()
    {
        return $this->isValid();
    }
    /***
     * Return a description of the error that occurred when the transaction
     * first became invalid. If the transaction is valid, the empty string is
     * returned.
     * @returns string
     ***/
    function errorMessage()
    {
        return $this->errorMessage;
    }

    
    /***
     * Return the error code that occurred when the transaction
     * first became invalid. If the transaction is valid, the empty string is
     * returned.
     * @returns string
     ***/
    function errorCode()
    {
        return $this->errorCode;
    }
    
    /***
     * Return the error message and error code that occurred when the transaction
     * first became invalid. If the transaction is valid, both strings are empty
     * @returns array
     ***/
    function error()
    {
        return array('message'=>$this->errormessage(), 'code'=>$this->errorcode());
    }

    
    function setError($message, $code)
    {
        $this->errorMessage = $message;
        $this->errorCode = $code;
    
    }
}


class NICED_Result {

	protected $db;
	protected $rst;
	protected $sql;
	protected $errorMessage;
	protected $errorCode;
	protected $rowCallbacks = array();

	public function __construct($db, $rst, $sql) {
		$this->db = $db;
		$this->rst = $rst;
		$this->sql = $sql;

		if (!$this->isSuccess()) {
			$this->errorMessage = $this->db->error;
			$this->errorCode = $this->db->errno;
		}
	}

	public function fetchRow($type = null) {
		$row = array();
		switch ($type) {
			case MYSQLI_ASSOC : {
				$row = $this->rst->fetch_assoc();
				break;
			}
			case MYSQLI_NUM : {
				$row = $this->rst->fetch_row();
				break;
			}
			case MYSQLI_BOTH : {
			}
			default : {
				$row = $this->rst->fetch_array();
			}
		}

		//call user callback
		for($i = 0; $i < sizeof($this->rowCallbacks); $i++) 
		{
			$row = call_user_func($this->rowCallbacks[$i], $row);
		}

		return $row;
	}

	public function fetchAll($type = null) {
		$rows = array();
		while ($row = $this->fetchRow($type)) {
			$rows[] = $row;
		}	

		return $rows;		
	}

    public function addRowManipulator($cb)
    {
        $this->rowCallbacks[] = $cb;
    
    }

	public function insertId() {
		if (false !== strpos($this->sql, 'insert')) {
			return $this->db->insert_id;
		}
		return false;
	}

	public function isSuccess() {
		 return (false == $this->rst) ? 0 : 1;
	}

	public function free() {
		$this->rst->free();
	}

	public function getRst()
	{
		return $this->rst;
	}

	public function errorMessage() {
		return $this->errorMessage;
	}

	public function errorCode() {
		return $this->errorCode;
	}
}//end class
