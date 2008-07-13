<?php
/**
 * Classe responsável para poder realizar consultas união.
 *
 * @author Hugo Ferreira da Silva
 * @package Lumine
 */

/**
 * Classe responsável para poder realizar consultas união.
 *
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br/lumine
 * @package Lumine
 */
class Lumine_Union extends Lumine_Base
{
	
	private $_union          = array();

	function __construct( $cfg )
	{
		$clname = 'Lumine_Dialect_' . $cfg->getProperty('dialect');
		$this->_bridge = new $clname( $this );
		$this->_config = $cfg;
	}

	public function add(Lumine_Base $obj)
	{
		$this->_union[] = $obj;
		return $this;
	}

	public function where($str = null)
	{
		if(is_null($str))
		{
			$this->_where = array();
		} else {
			$this->_where[] = $str;
		}
		return $this;
	}
	
	public function order($str = null)
	{
		if(is_null($str))
		{
			$this->_order = array();
		} else {
			$this->_order[] = $str;
		}
		return $this;
	}
	
	public function having($str = null)
	{
		if(is_null($str))
		{
			$this->_having = array();
		} else {
			$this->_having[] = $str;
		}
		return $this;
	}
	
	public function group($str = null)
	{
		if(is_null($str))
		{
			$this->_group = array();
		} else {
			$this->_group[] = $str;
		}
		return $this;
	}
	
	public function count($what='*')
	{
		$sql = "SELECT COUNT({$what}) as lumine_count FROM ( " . $this->getSQL() . ") as consulta";
		$res = $this->_execute($sql);
		
		if($res == true)
		{
			$total = $this->_bridge->fetch();
			return $total['lumine_count'];
		}
		
		return 0;
	}
	
	public function find( $auto_fetch = false )
	{
		$sql = $this->getSQL();
		
		$result = $this->_execute($sql);
		
		if($result == true)
		{
			if($auto_fetch == true)
			{
				$this->fetch();
			}
		}
		
		$this->dispatchEvent('posFind', $this);
		
		return $this->_bridge->num_rows();
		
	}
	
	public function limit($offset = null, $limit = null)
	{
		if( empty($limit))
		{
			$this->_limit = $offset;
		} else {
			$this->_offset = $offset;
			$this->_limit = $limit;
		}
		
		return $this;
	}

	public function getSQL()
	{
		if( empty($this->_union))
		{
			Lumine_Log::warning('Nenhuma classe incluida para realizar a união');
			return false;
		}
		
		$sql = array();
		foreach($this->_union as $obj)
		{
			$sql[] = "(" . trim( $obj->_getSQL(Lumine_Base::SQL_SELECT) ) . ")";
		}
		
		$strSQL = implode(PHP_EOL . ' UNION ' . PHP_EOL, $sql);
		
		if( !empty($this->_where))
		{
			$strSQL .= PHP_EOL . " WHERE " . implode(' AND ', $this->_where);
		}

		if( !empty($this->_group))
		{
			$strSQL .= PHP_EOL . " GROUP BY " . implode(', ', $this->_group);
		}
		
		if( !empty($this->_having))
		{
			$strSQL .= PHP_EOL . " HAVING " . implode(' AND ', $this->_having);
		}
		
		if( !empty($this->_order))
		{
			$strSQL .= PHP_EOL . " ORDER BY " . implode(', ', $this->_order);
		}
		
		$strSQL .= PHP_EOL . $this->_union[0]->_getConnection()->setLimit($this->_offset, $this->_limit);
		
		return $strSQL;
	}


	public function join( Lumine_Base $obj, $type = 'INNER', $alias = '', $linkName = null, $linkTo = null, $extraCondition = null )
	{
		$this->negado();
	}	
	
	public function save( $whereAddOnly = false  )
	{
		$this->negado();
	}
	
	public function insert()
	{
		$this->negado();
	}
	
	public function update( $whereAddOnly = false )
	{
		$this->negado();
	}
	
	public function delete( $whereAddOnly = false )
	{
		$this->negado();
	}
	
	public function get( $pk, $pkValue = null )
	{
		$this->negado();
	}
	
	private function negado()
	{
		$x = debug_backtrace();
		
		$str = 'Rotina "' . $x[1]['function'] . '" negada nesta classe';
		Lumine_Log::warning( $str );
	}
}


?>