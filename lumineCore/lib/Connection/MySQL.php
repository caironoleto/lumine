<?php

Lumine::load('Connection_IConnection');

class Lumine_Connection_MySQL extends Lumine_EventListener implements ILumine_Connection
{

	const CLOSED           = 0;
	const OPEN             = 1;

	const SERVER_VERSION   = 10;
	const CLIENT_VERSION   = 11;
	const HOST_INFO        = 12;
	const PROTOCOL_VERSION = 13;
	const RANDOM_FUNCTION  = 'rand()';
	
	const ESCAPE_CHAR      = '\\';
	
	protected $_event_types = array(
		'preExecute','posExecute','preConnect','posConnect','preClose','posClose',
		'onExecuteError','onConnectionError'
	);
	
	private $conn_id;
	private $database;
	private $user;
	private $password;
	private $port;
	private $host;
	private $options;
	private $state;
	
	private static $instance = null;
	
	static public function getInstance()
	{
		if(self::$instance == null)
		{
			self::$instance = new Lumine_Connection();
		}
		
		return self::$instance;
	}
	
	public function connect()
	{
		if($this->conn_id && $this->state == self::OPEN)
		{
			Lumine_Log::debug( 'Utilizando conexão cacheada com '.$this->getDatabase());
			mysql_select_db($this->getDatabase(), $this->conn_id);
			return true;
		}

		$this->dispatchEvent('preConnect', $this);
		
		$hostString = $this->getHost();
		if($this->getPort() != '') 
		{
			$hostString .=  ':' . $this->getPort();
		}
		if(isset($this->options['socket']) && $this->options['socket'] != '')
		{
			$hostString .= ':' . $this->options['socket'];
		}
		$flags = isset($this->options['flags']) ? $this->options['flags'] : null;
					
		if(isset($this->options['persistent']) && $this->options['persistent'] == true)
		{
			Lumine_Log::debug( 'Criando conexão persistente com '.$this->getDatabase());
			$this->conn_id = @mysql_pconnect($hostString, $this->getUser(), $this->getPassword(), $flags);
		} else {
			Lumine_Log::debug( 'Criando conexão com '.$this->getDatabase());
			$this->conn_id = @mysql_connect($hostString, $this->getUser(), $this->getPassword(), $flags);
		}
		
		if( !$this->conn_id )
		{
			$this->state = self::CLOSED;
			$msg = 'Não foi possível conectar no banco de dados: ' . $this->getDatabase().' - '.$this->getErrorMsg();
			Lumine_Log::error( $msg );
			
			$this->dispatchEvent('onConnectionError', $this, $msg);
			throw new Exception( $msg );
			
			return false;
		}
		
		// seleciona o banco
		mysql_select_db($this->getDatabase(), $this->conn_id);
		$this->state = self::OPEN;
		
		$this->dispatchEvent('posConnect', $this);
		
		return true;
	}
	
	public function close()
	{
		$this->dispatchEvent('preClose', $this);
		if($this->conn_id && $this->state != self::CLOSED)
		{
			$this->state = self::CLOSED;
			Lumine_Log::debug( 'Fechando conexão com '.$this->getDatabase());
			mysql_close($this->conn_id);
		}
		$this->dispatchEvent('posClose', $this);
	}
	
	public function getState()
	{
		return $this->state;
	}
	
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function getDatabase()
	{
		return $this->database;
	}
	
	public function setUser($user)
	{
		$this->user = $user;
	}
	public function getUser()
	{
		return $this->user;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}
	public function getPassword()
	{
		return $this->password;
	}

	public function setPort($port)
	{
		$this->port = $port;
	}
	public function getPort()
	{
		return $this->port;
	}
	
	public function setHost($host)
	{
		$this->host = $host;
	}
	public function getHost()
	{
		return $this->host;
	}
	
	public function setOptions($options)
	{
		$this->options = $options;
	}
	
	public function getOptions()
	{
		return $this->options;
	}
	
	public function setOption($name, $val)
	{
		$this->options[ $name ] = $val;
	}
	
	public function getOption($name)
	{
		if(empty($this->options[$name]))
		{
			return null;
		}
		return $this->options[$name];
	}

	public function getErrorMsg()
	{
		$msg = '';
		if($this->conn_id) 
		{
			$msg = mysql_error($this->conn_id);
		} else {
			$msg = mysql_error();
		}
		return $msg;
	}
	
	public function getTables()
	{
		if( ! $this->connect() )
		{
			return false;
		}
		
		$rs = $this->executeSQL("show tables");
		
		$list = array();
		
		while($row = mysql_fetch_row($rs))
		{
			$list[] = $row[0];
		}
		return $list;
	}
	
	public function getForeignKeys($tablename)
	{
		if( ! $this->connect() )
		{
			return false;
		}
		
		$fks = array();
		$rs = $this->executeSQL("SHOW CREATE TABLE ".$tablename);
		
		$result = mysql_fetch_row($rs);
		$result[0] = preg_replace("(\r|\n)",'\n', $result[0]);
		$matches = array();

		preg_match_all('@FOREIGN KEY \(`([a-z,A-Z,0-9,_]+)`\) REFERENCES `([a-z,A-Z,0-9,_]+)` \(`([a-z,A-Z,0-9,_]+)`\)(.*?)(\r|\n|\,)@i', $result[1], $matches);
		
		for($i=0; $i<count($matches[0]); $i++)
		{
			$name = $matches[2][$i];
			if(isset($fks[ $name ]))
			{
				$name = $name . '_' . $matches[3][$i];
			}
			
			$fks[ $name ]['from'] = $matches[1][$i];
			$fks[ $name ]['to'] = $matches[2][$i];
			$fks[ $name ]['to_column'] = $matches[3][$i];
			
			$reg = array();
			if(preg_match('@(.*?)ON UPDATE (RESTRICT|CASCADE)@i', $matches[4][$i], $reg))
			{
				$fks[ $name ]['update'] = strtoupper($reg[2]);
			} else {
				$fks[ $name ]['update'] = 'RESTRICT';
			}
			if(preg_match('@(.*?)ON DELETE (RESTRICT|CASCADE)@i', $matches[4][$i], $reg))
			{
				$fks[ $name ]['delete'] = strtoupper($reg[2]);
			} else {
				$fks[ $name ]['delete'] = 'RESTRICT';
			}
			
		}
		
		return $fks;
	}
	
	public function getServerInfo($type = null)
	{
		if($this->conn_id && $this->state == self::OPEN)
		{
			switch($type)
			{
				case self::CLIENT_VERSION:
					return mysql_get_client_info();
					break;
				case self::HOST_INFO:
					return mysql_get_host_info($this->conn_id);
					break;
				case self::PROTOCOL_VERSION:
					return mysql_get_proto_info($this->conn_id);
					break;
				case self::SERVER_VERSION:
				default:
					return mysql_get_server_info($this->conn_id);
					break;
			}
			return '';
			
		} 
		throw new Lumine_Exception('A conexão não está aberta', Lumine_Exception::WARNING);
	}
	
	public function describe($tablename)
	{
		$sql = "DESCRIBE ". $tablename;
		$rs = $this->executeSQL( $sql );
		
		$data = array();
		while($row = mysql_fetch_row($rs))
		{
			$name           = $row[0];
			$type_native    = $row[1];
			if(preg_match('@(\w+)\((\d+)\)@', $row[1], $r))
			{
				$type       = $r[1];
				$length     = $r[2];
			} else {
				$type       = $row[1];
				$length     = null;
			}
			
			switch( strtolower($type) )
			{
				case 'tinyblob': $length = 255; break;
				case 'tinytext': $length = 255; break;
				case 'blob': $length = 65535; break;
				case 'text': $length = 65535; break;
				case 'mediumblob': $length = 16777215; break;
				case 'mediumtext': $length = 16777215; break;
				case 'longblob': $length = 4294967295; break;
				case 'longtext': $length = 4294967295; break;
				case 'enum': $length = 65535; break;
			}
			
			$notnull        = $row[2] == 'YES' ? false : true;
			$primary        = $row[3] == 'PRI' ? true : false;
			$default        = $row[4] == 'NULL' ? null : $row[4];
			$autoincrement  = $row[5] == 'auto_increment' ? true : false;
			
			$data[] = array($name, $type_native, $type, $length, $primary, $notnull, $default, $autoincrement);
		}
		return $data;
	}
	
	public function executeSQL($sql)
	{
		$this->dispatchEvent('preExecute', $this, $sql);
		$this->connect();
		$rs = @mysql_query($sql, $this->conn_id);
		
		if( ! $rs )
		{
			$msg = $this->getErrorMsg();
			$this->dispatchEvent('onExecuteError', $this, $sql, $msg);
			throw new Lumine_Exception("Falha na consulta: " . $msg, Lumine_Exception::QUERY_ERROR);
		}
		$this->dispatchEvent('posExecute', $this, $sql);
		return $rs;
	}
	
	public function setLimit($offset = null, $limit = null) 
	{
		if($offset == null && $limit == null)
		{
			return;
		} else if($offset == null && $limit != null) {
			return sprintf("LIMIT %d", $limit);
		} else if($offset != null && $limit == null) {
			return sprintf("LIMIT %d", $offset);
		} else {
			return sprintf("LIMIT %d, %d", $offset, $limit);
		}
	}
	
	public function escape($str) 
	{
		if($this->state == self::OPEN)
		{
			return mysql_real_escape_string($str, $this->conn_id);
		} else {
			return mysql_escape_string($str);
		}
	}
	
	public function escapeBlob($blob)
	{
		return $this->escape( $blob );
	}
	
	public function affected_rows()
	{
		if($this->state == self::OPEN)
		{
			return mysql_affected_rows($this->conn_id);
		}
		throw new Lumine_Exception('Conexão não está aberta', Lumine_Exception::ERRO);
	}
	
	public function num_rows($rs)
	{
		return mysql_num_rows($rs);
	}
	
	public function random()
	{
		return self::RANDOM_FUNCTION;
	}
	
	public function getEscapeChar()
	{
		return self::ESCAPE_CHAR;
	}
	
	// transações
	public function begin($transactionID=null)
	{
		$this->executeSQL("BEGIN");
	}
	public function commit($transactionID=null)
	{
		$this->executeSQL("COMMIT");
	}
	public function rollback($transactionID=null)
	{
		$this->executeSQL("ROLLBACK");
	}
	
    function __destruct()
    {
        unset($this->conn_id);
        unset($this->database);
        unset($this->user);
        unset($this->password);
        unset($this->port);
        unset($this->host);
        unset($this->options);
        unset($this->state);
        unset($this->transactions);
        unset($this->transactions_count);
        unset($this->instance);
        
        parent::__destruct();
    }
}


?>