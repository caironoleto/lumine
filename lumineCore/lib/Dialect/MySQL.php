<?php

Lumine::load('Dialect_Exception');
Lumine::load('Dialect_IDialect');

class Lumine_Dialect_MySQL extends Lumine_EventListener implements ILumine_Dialect
{

	private $connection = null;
	private $result_set = null;
	private $obj        = null;
	private $dataset    = array();
	private $pointer    = 0;
	private $fetchMode  = '';

	function __construct(Lumine_Base $obj = null)
	{
		//$this->obj = $obj;
		$this->setConnection( $obj->_getConnection() );
		$this->setFetchMode( $obj->fetchMode() );
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
					$native_mode = MYSQL_ROW;
				break;
				
				case Lumine_Base::FETCH_BOTH:
					$native_mode = MYSQL_BOTH;
				break;
				
				case Lumine_Base::FETCH_ASSOC:
				default:
					$native_mode = MYSQL_ASSOC;
			}
			
			//$this->pointer = 0;
			
			if( gettype($rs) != 'boolean')
			{
				$this->result_set = $rs;
				$this->dataset = array();

				/*while($row = mysql_fetch_array($this->result_set, $native_mode))
				{
					$this->dataset[] = $row;
				}*/
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
	
	/**
	 * retorna uma determinada linha
	 *
	 * @param int $rowNumber numero da linha
	 * @return array|boolean False se não houver a determinada linha, array de dados se encontrar
	 */
	public function fetch_row($rowNumber)
	{
	    if( $rowNumber < 0 || $rowNumber > $this->num_rows() - 1 )
	    {
	        return false;
	    }
	    
	    mysql_data_seek($this->result_set, $rowNumber);
	    
	    /*
		if( empty($this->dataset[ $rowNumber ]))
		{
			return false;
		}
		*/
		$this->setPointer($rowNumber);
		$row = mysql_fetch_assoc($this->result_set);
		
		return $row;
	}
	
	/**
	 * passa para o proximo registro
	 *
	 * @return array|boolean array se encontrar, false se não houver mais
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
		mysql_data_seek( $this->result_set, $this->pointer );
		$row = mysql_fetch_assoc($this->result_set);
		$this->pointer++;

		return $row;
	}
	
	/**
	 * retorna a mensagem de erro
	 *
	 * @return string mensagem de erro
	 */
	public function getErrorMsg()
	{
		if($this->getConnection() == null)
		{
			throw new Lumine_Dialect_Exception('Conexão não setada');
		}
		return $this->getConnection()->getErrorMsg();
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
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
		$cn = $this->getConnection();
		$rs = $cn->executeSQL("select last_insert_id() as id");
		if(mysql_num_rows($rs) > 0)
		{
			$ultimo_id = mysql_result($rs, 0, 0);
			mysql_free_result($rs);
			
			return $ultimo_id;
		}
		
		mysql_free_result($rs);
		return 0;
	}
	
	function __destruct()
	{
		$this->connection = null;
		$this->result_set = null;
		$this->obj = null;
		$this->dataset    = array();
		$this->pointer    = 0;
		
		parent::__destruct();
	}
}

?>