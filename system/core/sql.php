<?php
/**
 * @desc sql class file
 * @author nana
 * @date 2011
 *
 */
define('INT', 'I');
define('STRING', 'C');
define('STRING_NULL', 'CN');
define('FLOAT', 'N');
define('TIMESTAMP', 'T');
define('BINARY', 'B');
define('EVALUATE', 'E');
define('TIMESTAMP_AUTO', 'TA');
define('UNIX_TIMESTAMP', 'UT');
define('UNIX_TIMESTAMP_AUTO', 'UTA');
define('SERIALIZE', 'S');
define('SERIALIZE_NULL', 'SN');
/**
  * @usage:
  *
$sql= new SQL();
$sql->set('name', 'nana');
$sql->setFrom('mysql');
$sql->setOrder('id');
$sql->setType('insert');
//var_dump($sql->render());
  */
class SQL
{
	protected $from;
	protected $what;
	protected $useindex;
	protected $where;
	protected $group;
	protected $having;
	protected $order;
	protected $limit;
	protected $lock;
	protected $escapeMethod;
	protected $type;
	protected $delayed;
	protected $ignore;
	protected $duplicatekeyclause;

    function SQL($type = 'select', $from = NULL)
    {
        $this->reset();
        $this->setType($type);
        $this->setFrom($from);
    }

    function render( $type = null )
    {
        if( $type !== null ) $this->setType($type);
        switch( $this->type )
        {
            case 'change' :
            case 'replace': return $this->renderReplace();
            case 'update' : return $this->renderUpdate();
            case 'insert' : return $this->renderInsert();
            case 'delete' : return $this->renderDelete();
            case 'select' : return $this->renderSelect();
            default : return '';
        }
    }

    function toString($type = null){ return $this->render($type); }

    function renderSelect() {return $this->renderSelectStmt($this->from());}

    function renderSelectStmt($from)
    {
        $stmt = sprintf( "SELECT %s FROM %s", $this->what(), $from );
        if( $useindex = $this->useIndex() ) $stmt .= sprintf( " USE INDEX (%s)", $useindex );
        if( $where = $this->where()  ) $stmt .= sprintf( " WHERE %s", $where );
        if( $group = $this->group()  ) $stmt .= sprintf( " GROUP BY %s", $group );
        if( $having  = $this->having() ) $stmt .= sprintf( " HAVING %s", $having );
        if( $order = $this->order()  ) $stmt .= sprintf( " ORDER BY %s", $order );
        if( $lock  = $this->lock()   ) $stmt .= " FOR UPDATE";
        if( $limit = $this->limit()  ) $stmt .= sprintf( " LIMIT %s", $limit );
        return trim($stmt);
    }

    function renderInsert(){ return $this->_renderInto('INSERT'); }

    function renderReplace(){ return $this->_renderInto('REPLACE'); }

    function _renderInto( $type )
    {
        if( $this->ignore() ) $type .= ' IGNORE';
        if( $this->delayed() ) $type .= ' DELAYED';
        if (isset($this->duplicatekeyclause)) {
            $duplicatekeyclause = sprintf(" ON DUPLICATE KEY UPDATE %s ",$this->duplicatekeyclause);
		}else{
			$duplicatekeyclause = "";
		}
        if (!$this->selectFrom())
        {
            $names = array();
            $values = array();
            foreach($this->pairs as $pair)
            {
                $names[] = '`' . $pair['name'] . '`';
                $values[] = $this->format($pair['value'], $pair['type']);
            }
            return sprintf( "%s INTO %s (%s) VALUES (%s) %s", $type, $this->from(), implode(', ', $names), implode(', ', $values), $duplicatekeyclause);
        }
        return sprintf( "%s INTO %s %s", $type, $this->from(), $this->renderSelectStmt($this->selectFrom()));
    }

    function renderUpdate()
    {
        $cols = array();
        foreach($this->pairs as $pair)
        {
            $cols[] = sprintf('`%s`=%s', $pair['name'], $this->format($pair['value'], $pair['type']));
        }
        $what = implode(", ", $cols);
        $stmt = sprintf( "UPDATE %s SET %s", $this->from(), $what);
        if( $useindex = $this->useIndex()    ) $stmt .= sprintf( " USE INDEX (%s)", $useindex );
        if( $where = $this->where() ) $stmt .= sprintf( " WHERE %s", $where );
        if( $order = $this->order() ) $stmt .= sprintf( " ORDER BY %s", $order );
        if( $limit = $this->limit() ) $stmt .= sprintf( " LIMIT %s", $limit );
        return trim($stmt);
    }

    function renderDelete()
    {
        $stmt = sprintf( "DELETE FROM %s", $this->from());
        if( $where = $this->where() ) $stmt .= sprintf( " WHERE %s", $where );
        if( $limit = $this->limit() ) $stmt .= sprintf( " LIMIT %s", $limit );
        return trim($stmt);
    }

    function reset()
    {
        $this->pairs = array();
        $this->what = array();
        $this->useindex = array();
        $this->where = new SQLWhere('AND', FALSE);
        $this->group = array();
        $this->having = array();
        $this->order = array();
        $this->limit = array();
        $this->lock = FALSE;
    }
    function set($n, $v, $t=STRING)
    {
        $this->pairs[$n] = array('name'=>$n, 'value'=>$v, 'type'=>$t);
        return $v;
    }

    function get( $n )
    {
        return ( isset( $this->pairs[$n] ) ) ? $this->pairs[$n]['value'] : NULL;
    }

    function setType( $v )
    {
        $v = strtolower($v);
        switch($v)
        {
            case 'change'  :
            case 'replace' :
                return $this->type = 'replace';

            case 'update' :
                return $this->type = 'update';

            case 'insert' :
                return $this->type = 'insert';

            case 'delete' :
                return $this->type = 'delete';

            case 'select' :
            default:
                return $this->type = 'select';
        }
    }

    function format($v, $t)
    {
        if( $t != SERIALIZE && is_array( $v ) ) {
            $e = new Exception('format err');
            syslog(LOG_INFO, 'PHP SQL format err: ' . $e->getTraceAsString());
        }
        switch($t)
        {
            case INT :
                    if(!preg_match("/^[-+]?[0-9]+$/", $v)) return (empty($v)) ? "'0'" : "NULL";
                    return "'" . $v . "'";

            case FLOAT :
                    if(!preg_match("/^[-+]?([0-9]*\.)?[0-9]+$/", $v)) return "NULL";
                    return "'" . $v . "'";

            case TIMESTAMP :
                    if(empty($v)) return 'NULL';
                    return "'" .  date('Y/m/d H:i:s', (   is_numeric($v) ? $v :  strtotime(trim($v)))       ) . "'";

			case TIMESTAMP_AUTO :
            		if( $v === NULL ) $v = SC::get('board_config.time_now');
					if(empty($v)) return 'NULL';
                    return "'" .  date('Y/m/d H:i:s', (   is_numeric($v) ? $v :  strtotime(trim($v)))       ) . "'";

            case UNIX_TIMESTAMP :
                    if(!preg_match("/^[-+]?[0-9]+$/", $v)) return (empty($v)) ? "'0'" : "NULL";
                    return "'" . $v . "'";

            case UNIX_TIMESTAMP_AUTO :
            		if( $v === NULL ) return SC::get('board_config.time_now');
                    if(!preg_match("/^[-+]?[0-9]+$/", $v)) return (empty($v)) ? "'0'" : "NULL";
                    return "'" . $v . "'";

            case EVALUATE :
                    return $v;

            case BINARY :
                    return "'" . addslashes($v) . "'";


            case STRING_NULL :
            case SERIALIZE_NULL:
                    if( $v === NULL ) return 'NULL';

            case SERIALIZE :
            case SERIALIZE_NULL :
                    if( ! is_scalar( $v ) ) $v = serialize( $v );
                    return "'" . addslashes($v) . "'";

            case STRING :
            default  :
                    return "'" . $this->escape($v) . "'";
        }
    }
    function escape($s)
    {
        if(empty($this->escapeMethod)) return addslashes(stripslashes($s));
        return call_user_func($this->escapeMethod, $s);
    }
    function setEscapeMethod($method){ $this->escapeMethod = $method; }

    function pairs()
    {
        return $this->pairs;
    }

    function setWhat($v)
    {
        $this->what = array();
        return $this->appendWhat( $v );
    }

    function appendWhat( $v )
    {
        if( !is_array( $v ) ) $v = explode(',', $v);
        foreach( $v as $k=>$e )
        {
            if( preg_match('#^(.+?) as (.+?)$#i', $e, $matches) ) list( $base, $k, $e) = $matches;
            $k = trim($k);
            $e = trim($e);
            if( strlen( $e ) < 1 ) continue;
            if( is_numeric( $k ) )
            {
                $this->what[] = $e;
            }
            else
            {
                $this->what[$k] = $e;
            }
        }
        return $this->what;
    }

    function setFrom($v)
    {
        $this->from = array();
        return $this->appendFrom( $v );
    }

    function appendFrom($v)
    {
        $this->from[] = trim($v);
    }

    function setSelectFrom($v)
    {
        $this->selectFrom = $v;
    }


    function setUseIndex($v)
    {
        $this->useindex = array();
        return $this->appendUseIndex( $v );
    }

    function appendUseIndex($v)
    {
        $this->useindex[] = trim($v);
    }

    function onDuplicateKeyClause($v)
	{
        $this->duplicatekeyclause = trim($v);
	}

	function duplicatekeyclause(){
	    return $this->duplicatekeyclause;
	}

    function setWhere($v)
    {
        $this->where = new SQLWhere($this->operator(), FALSE );
        return $this->appendWhere($v);
    }

    function appendWhere($v)
    {
        if( ! is_array( $v) ) $v = array( $v );
        foreach($v as $e ) $this->where->set($e);
        return $this->where;
    }

    function setGroup($v)
    {
        $this->group = array();
        return $this->appendGroup( $v );
    }

    function appendGroup($v)
    {
        $this->group[] = trim($v);
    }

    function setHaving($v)
    {
        $this->having = array();
        return $this->appendHaving( $v );
    }

    function appendHaving($v)
    {
        $this->having[] = trim($v);
    }

    function setOrder($v)
    {
        $this->order = array();
        return $this->appendOrder( $v );
    }

    function appendOrder( $v )
    {
        if( !is_array( $v ) ) $v = explode(',', $v);
        foreach( $v as $k=>$e )
        {
            if( preg_match('#^(.+?) (.+?)$#i', $e, $matches) ) list( $base, $k, $e) = $matches;
            $k = trim($k);
            $e = trim($e);

            if( is_numeric( $k ) )
            {
                $this->order[] = $e;
            }
            else
            {
                $this->order[$k] = $e;
            }
        }
        return $this->order;
    }

    function setLimit($v = NULL, $e = NULL)
    {
        if( $v === NULL ) return $this->limit = array();
        if( is_numeric( $v ) )
        {
            $this->limit = array(intval($v) );
            if( is_numeric( $e ) ) $this->limit[] = intval( $e );
            return $this->limit;
        }
        if( is_array( $v ) ) $v = implode(',', $v );
        if( $e !== NULL ) $v .= ',' . trim($e);
        $v = trim( $v );
        if( preg_match('#^([0-9]+?)[ ]?,[ ]?([0-9]+?)$#', $v, $matches) ) return $this->limit = array( intval($matches[1]), intval($matches[2]) );
        return $this->limit = ( preg_match('#([0-9]+?)$#', $v, $matches) ) ? array( intval($matches[1]) ) : array();
    }

    function setOperator($v)
    {
        $this->where->setOperator($v);
    }

    function selectLock( $v = TRUE)
    {
        $this->lock = ( $v ) ? TRUE : FALSE;
    }

    function useDelayed($v = TRUE)
    {
    	$this->delayed = ( $v ) ? TRUE : FALSE;
    }

    function useIgnore($v = TRUE)
    {
    	$this->ignore = ( $v ) ? TRUE : FALSE;
    }

    function operator()
    {
        return $this->where->operator();
    }

    function from()
    {
        return implode(', ', $this->from);
    }

    function selectFrom()
    {
        return isset($this->selectFrom) ? $this->selectFrom:false;
    }

    function useindex()
    {
        return implode(', ', $this->useindex);
    }

    function what()
    {
        if ( empty( $this->what ) ) return '*';
        $what = array();
        foreach( $this->what as $k=>$v )
        {
            if( ! is_numeric( $k ) ) $v = "$k AS $v";
            $what[] = $v;
        }
        return implode(', ', $what);
    }
    function where()
    {
        return $this->where->render();
    }

    function group()
    {
        return implode(', ', $this->group);
    }

    function having()
    {
        return implode(', ', $this->having);
    }

    function order()
    {
        if ( empty( $this->order ) ) return '';
        $order = array();
        foreach( $this->order as $k=>$v )
        {
            if( ! is_numeric( $k ) ) $v = "$k $v";
            $order[] = $v;
        }
        return implode(', ', $order);
    }

    function limit()
    {
        return implode(', ', $this->limit);
    }

    function lock()
    {
        return $this->lock;
    }

    function delayed()
    {
    	return $this->delayed;
    }

    function ignore(){
        return $this->ignore;
    }

    function type()
    {
    	return $this->type;
    }

    // Force a copy of SQLWhere for cloning (support for deep cloning. used in item norm daos)
    function __clone()
    {
        $this->where = clone $this->where;
    }

}//end class

class SQLWhere
{
	protected $phrases;
	protected $operator;

    public function SQLWhere($op='AND', $subclause = TRUE)
    {
        $this->phrases = array();
        $this->setOperator($op);
        $this->subclause = ( $subclause ) ? TRUE : FALSE;
    }
    public function set($phrase)
    {
        if( $phrase instanceof SQLWhere ) $phrase = $phrase->render();
        if( is_scalar( $phrase ) && strlen( $phrase ) > 0 ) $this->phrases[] = $phrase;
    }
    public function toString()
    {
        return $this->render();
    }

    public function render()
    {
        if(empty($this->phrases)) return '';
        $string = implode(" " . $this->operator . " ", $this->phrases);
        return ( $this->subclause  ) ? "(" . $string . ")" : $string;    }

    public function setOperator($op)
    {
        $this->operator = (strtoupper($op)=='OR') ? 'OR' : 'AND';
    }

    public function operator()
    {
        return $this->operator;
    }

} //end class
