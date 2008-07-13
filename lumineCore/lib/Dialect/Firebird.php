<?php

Lumine::load('Dialect_Exception');
Lumine::load('Dialect_IDialect');

class Lumine_Dialect_Firebird extends Lumine_EventListener implements ILumine_Dialect
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
		$this->setTablename($obj->tablename());
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
		
			//$this->pointer = 0;
			
			if( is_resource($rs) )
			{
				$this->result_set = $rs;
				$this->dataset = array();

				while($row = ibase_fetch_assoc($this->result_set, IBASE_FETCH_BLOBS))
				{
					$this->dataset[] = $row;
				}
				$this->pointer = 0;
				return true;
			} else {
				return $rs;
			}
			
		} catch (Exception $e) {
			Lumine_Log::warning( 'Falha na consulta: ' . $cn->getErrorMsg());
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
	public function fetch_row($rowNumber)
	{
		if( empty($this->dataset[ $rowNumber ]))
		{
			return false;
		}
		$this->setPointer($rowNumber);
		
		return $this->dataset[ $rowNumber ] ;
	}
	public function fetch()
	{
		if( ! empty($this->dataset[ $this->pointer ]))
		{
			Lumine_Log::debug( 'Retornando linha: '.$this->pointer);
			$row = $this->dataset[ $this->pointer];
			$this->pointer++;
			
			return $row;
		}

		Lumine_Log::debug( 'Nenhum resultado para o cursor '.$this->pointer);
		$this->moveFirst();
		return false;
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
		//////////////////////////////////////////////////////////////////
		// GAMBIARRA FORTE!
		// Aqui pegamos as triggers relacionadas a tabela
		// e procuramos no corpo da trigger uma linha semelhante a
		// new.nome_campo = gen_id(nome_sequencia, 1)
		// para pegarmos o nome da sequencia e consequentemente
		// podermos recuperar o ultimo valor
		//////////////////////////////////////////////////////////////////
		
		$cn = $this->getConnection();
		
		$sql = "SELECT RDB\$TRIGGER_SOURCE AS triggers FROM RDB\$TRIGGERS
				 WHERE (RDB\$SYSTEM_FLAG IS NULL
					OR RDB\$SYSTEM_FLAG = 0)
				   AND RDB\$RELATION_NAME='".$this->getTablename()."'";
		
		$rs = $cn->executeSQL($sql);
		
		while( $row = ibase_fetch_assoc($rs, IBASE_FETCH_BLOBS) )
		{
			// oba! achamos o lance
			$exp = '@new\.'.$campo.'\s+=\s+gen_id\((\w+)@is';
			$res = preg_match($exp, trim($row['TRIGGERS']), $reg);
			
			if( $res )
			{
				ibase_free_result($rs);
				$sql = "SELECT GEN_ID(".$reg[1].", 0) as lastid FROM RDB\$DATABASE";
				$rs = $cn->executeSQL($sql);
				
				$row = ibase_fetch_row($rs);
				ibase_free_result($rs);
				
				return $row[0];
			}
		}
		
		ibase_free_result($rs);
		return 0;
	}
}

?>