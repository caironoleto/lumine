<?php

Lumine::load('Dialect_Exception');
Lumine::load('Dialect_IDialect');

class Lumine_Dialect_PostgreSQL extends Lumine_EventListener implements ILumine_Dialect
{

	private $connection = null;
	private $result_set = null;
	private $obj        = null;
	private $dataset    = array();
	private $pointer    = 0;
	private $fetchMode;
	private $tablename;

	function __construct(Lumine_Base $obj = null)
	{
		//$this->obj = $obj;
		$this->setConnection( $obj->_getConnection() );
		$this->setFetchMode( $obj->fetchMode() );
		$this->setTablename( $obj->tablename() );
	}

	public function setConnection($cnn)
	{
		$this->connection = $cnn;
	}

	public function getConnection()
	{
		return $this->connection;
	}
	
	public function getFetchMode()
	{
		return $this->fetchMode;
	}
	
	public function setFetchMode( $mode )
	{
		$this->fetchMode = $mode;
	}
	
	public function getTablename()
	{
	    return $tablename;
	}
	
	public function setTablename( $tablename )
	{
	    $this->tablename = $tablename;
	}

	public function execute($sql)
	{
		$cn = $this->getConnection();
		if( $cn == null )
		{
			throw new Lumine_Dialect_Exception('Conexão não setada');
		}

		$cn->connect();		
		$this->setConnection($cn);
		
		try
		{
			Lumine_Log::debug( 'Executando consulta: ' . $sql);
			$rs = $cn->executeSQL($sql);
			
			$mode = $this->getFetchMode();
			$native_mode = null;
			switch($mode)
			{
				case Lumine_Base::FETCH_ROW:
					$native_mode = PGSQL_NUM;
				break;
				
				case Lumine_Base::FETCH_BOTH:
					$native_mode = PGSQL_BOTH;
				break;
				
				case Lumine_Base::FETCH_ASSOC:
				default:
					$native_mode = PGSQL_ASSOC;
			}
			
			
			
			if( gettype($rs) != 'boolean')
			{
				$this->result_set = $rs;
				$this->dataset = array();
				
				/*
				if($this->getConnection()->num_rows($this->result_set) > 0)
				{
					while($row = pg_fetch_array($this->result_set, null, $native_mode))
					{
						$this->dataset[] = $row;
					}
				}*/
			
				$this->pointer = 0;
				return true;
			} else {
				return $rs;
			}
			
		} catch (Exception $e) {
			Lumine_Log::warning('Falha na consulta: ' . $cn->getErrorMsg());
			return false;
		}
	}
	public function num_rows()
	{
		if( empty($this->result_set) )
		{
			Lumine_Log::warning('A consulta deve primeiro ser executada');
			return 0;
		}
		
		return $this->getConnection()->num_rows($this->result_set);
	}
	public function affected_rows()
	{
		$cn = $this->getConnection();
		if( empty($cn) )
		{
			throw new Lumine_Dialect_Exception('Conexão não setada');
		}
		return $cn->affected_rows();
	}
	public function moveNext()
	{
		$this->pointer++;
		if($this->pointer >= $this->num_rows())
		{
			$this->pointer = $this->num_rows() - 1;
		}
	}
	public function movePrev()
	{
		$this->pointer--;
		if($this->pointer < 0)
		{
			$this->pointer = 0;
		}
	}
	public function moveFirst()
	{
		$this->pointer = 0;
	}
	public function moveLast()
	{
		$this->pointer = $this->num_rows() - 1;
		if($this->pointer < 0)
		{
			$this->pointer = 0;
		}
	}
	
	/**
	 * recupera uma determinada linha
	 *
	 * @param int $rowNumber
	 * @return array|boolean
	 */
	public function fetch_row($rowNumber)
	{
	    if( $rowNumber < 0 || $rowNumber > $this->num_rows() - 1 )
	    {
	        return false;
	    }
	    
		$this->setPointer($rowNumber);
		
		return pg_fetch_assoc($this->result_set, $rowNumber);
	}
	
	/**
	 * passa para o proximo registro se houver
	 *
	 * @return array|boolean
	 */
	public function fetch()
	{
	    if( $this->pointer < 0 || $this->pointer > $this->num_rows() - 1 )
	    {
	        Lumine_Log::debug( 'Nenhum resultado para o cursor '.$this->pointer);
	        $this->moveFirst();
	        return false;
	    }
	    
	    
		Lumine_Log::debug( 'Retornando linha: '.$this->pointer);
		
		$row = pg_fetch_assoc($this->result_set, $this->pointer);
		$this->pointer++;

		return $row;
	}
	public function getErrorMsg()
	{
		if($this->getConnection() == null)
		{
			throw new Lumine_Dialect_Exception('Conexão não setada');
		}
		return $this->getConnection()->getErrorMsg();
	}

	public function getDataset()
	{
		return $this->dataset;
	}
	
	public function setDataset(array $dataset)
	{
		$this->dataset = $dataset;
	}
	
	public function getPointer()
	{
		return $this->pointer;
	}
	public function setPointer($pointer)
	{
		$this->pointer = $pointer;
	}
	
	public function getLumineType($nativeType)
	{
		// inteiros
		if(preg_match('@^(int|integer|longint|mediumint)$@i', $nativeType))
		{
			return 'int';
		}
		// textos longos
		if(preg_match('@^(text|mediumtext|tinytext|longtext|enum)$@i', $nativeType))
		{
			return 'text';
		}
		// booleanos
		if(preg_match('@^(tinyint|boolean|bool)$@i', $nativeType))
		{
			return 'boolean';
		}
		// datas
		if(preg_match('@^timestamp@i', $nativeType))
		{
			return 'datetime';
		}
		return $nativeType;
	}
	
	/**
	 * Retorna o ultimo ID da tabela para campos auto-increment
	 * @author Hugo Ferreira da Silva
	 * @param string $campo Nome do campo da tabela de auto-increment
	 * @return int Valor da ultima inserção
	 */
	public function getLastId( $campo )
	{
	
		$sql = "SELECT currval( s2.nspname || '.' || t2.relname ) AS id
				FROM pg_depend AS d
				JOIN pg_class AS t1 ON t1.oid = d.refobjid
				JOIN pg_class AS t2 ON t2.oid = d.objid
				JOIN pg_namespace AS s1 ON s1.oid = t1.relnamespace
				JOIN pg_namespace AS s2 ON s2.oid = t2.relnamespace
				JOIN pg_attribute AS a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
				WHERE t1.relkind = 'r'
				AND t2.relkind = 'S'
				AND t1.relname = '".$this->getTablename()."'
				AND attname = '".$campo."'";
		
		$cn = $this->getConnection();
		
		$rs = $cn->executeSQL( $sql );
		if(pg_num_rows($rs) > 0)
		{
			$line = pg_fetch_row($rs);
			pg_free_result($rs);
			
			return $line[0];
		}
		
		pg_free_result($rs);
		return 0;
	}
}

?>