<?php

Lumine::load('Connection_IConnection');

class Lumine_Connection_PostgreSQL extends Lumine_EventListener implements ILumine_Connection
{

	const CLOSED           = 0;
	const OPEN             = 1;

	const SERVER_VERSION   = 10;
	const CLIENT_VERSION   = 11;
	const HOST_INFO        = 12;
	const PROTOCOL_VERSION = 13;
	const RANDOM_FUNCTION  = 'random()';
	
	const ESCAPE_CHAR      = '\'';
	
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
	private $last_rs;
	private static $state;
	
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
		if($this->conn_id && self::$state == self::OPEN)
		{
			Lumine_Log::debug( 'Utilizando conexão cacheada com '.$this->getDatabase());
			return true;
		}

		$this->dispatchEvent('preConnect', $this);
		
		$hostString = 'host='.$this->getHost();
		$hostString .=  ' dbname=' . $this->getDatabase();
		if($this->getPort() != '') 
		{
			$hostString .=  ' port=' . $this->getPort();
		}
		
		if($this->getUser() != '') 
		{
			$hostString .=  ' user=' . $this->getUser();
		}
		
		if($this->getPassword() != '') 
		{
			$hostString .=  ' password=' . $this->getPassword();
		}
		
		if(isset($this->options['socket']) && $this->options['socket'] != '')
		{
			$hostString .= ' socket=' . $this->options['socket'];
		}
		$flags = isset($this->options['flags']) ? $this->options['flags'] : null;
					
		if(isset($this->options['persistent']) && $this->options['persistent'] == true)
		{
			Lumine_Log::debug('Criando conexão persistente com '.$this->getDatabase());
			$this->conn_id = pg_pconnect($hostString);
		} else {
			Lumine_Log::debug('Criando conexão com '.$this->getDatabase());
			$this->conn_id = pg_connect($hostString);
		}
		
		if( !$this->conn_id )
		{
			self::$state = self::CLOSED;
			$msg = 'Não foi possível conectar no banco de dados: ' . $this->getDatabase().' - '.$this->getErrorMsg();
			Lumine_Log::error( $msg );
			
			$this->dispatchEvent('onConnectionError', $this, $msg);
			throw new Exception( $msg );
			
			return false;
		}
		
		self::$state = self::OPEN;
		
		$this->dispatchEvent('posConnect', $this);
		
		return true;
	}
	
	public function close()
	{
		$this->dispatchEvent('preClose', $this);
		if($this->conn_id && self::$state != self::CLOSED)
		{
			self::$state = self::CLOSED;
			Lumine_Log::debug( 'Fechando conexão com '.$this->getDatabase());
			pg_close($this->conn_id);
		}
		$this->dispatchEvent('posClose', $this);
	}
	
	public function getState()
	{
		return self::$state;
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
			$msg = pg_last_error($this->conn_id);
		} else {
			$msg = pg_last_error();
		}
		return $msg;
	}
	
	public function getTables()
	{
		if( ! $this->connect() )
		{
			return false;
		}
		
		$sql = "select tablename from pg_tables where tablename not like 'pg\_%'
				and tablename not in ('sql_features', 'sql_implementation_info', 'sql_languages',
				'sql_packages', 'sql_sizing', 'sql_sizing_profiles','sql_parts') ";
				
		$rs = $this->executeSQL($sql);
		
		$list = array();
		
		while($row = pg_fetch_row($rs))
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
		
		$sql = "SELECT pg_catalog.pg_get_constraintdef(r.oid, true) as condef
				FROM pg_catalog.pg_constraint r, pg_catalog.pg_class c
				WHERE r.conrelid = c.oid AND r.contype = 'f'
				AND c.relname = '".$tablename."'";

		$fks = array();
		$rs = $this->executeSQL($sql);
		
		
		while($row = pg_fetch_row($rs))
		{
//						FOREIGN KEY (idusuario) REFERENCES usuario(idusuario) ON UPDATE CASCADE ON DELETE CASCADE
			preg_match('@FOREIGN KEY \((\w+)\) REFERENCES (\w+)\((\w+)\)(.*?)$@i', $row[0], $matches);

			$name = $matches[2];
			if(isset($fks[ $name ]))
			{
				$name = $name . '_' . $matches[3];
			}
			
			$fks[ $name ]['from'] = $matches[1];
			$fks[ $name ]['to'] = $matches[2];
			$fks[ $name ]['to_column'] = $matches[3];
			
			$reg = array();
			if(preg_match('@(.*?)ON UPDATE (RESTRICT|CASCADE)@i', $matches[4], $reg))
			{
				$fks[ $name ]['update'] = strtoupper($reg[2]);
			} else {
				$fks[ $name ]['update'] = 'RESTRICT';
			}
			if(preg_match('@(.*?)ON DELETE (RESTRICT|CASCADE)@i', $matches[4], $reg))
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
		if($this->conn_id && self::$state == self::OPEN)
		{
			switch($type)
			{

			}
			return '';
			
		} 
		throw new Lumine_Exception('A conexão não está aberta', Lumine_Exception::WARNING);
	}
	
	public function describe($tablename)
	{
	
		$sql = "
		SELECT
			a.attname, t.typname, 
			CASE
				WHEN t.typlen < 0 THEN CASE WHEN a.atttypmod > 0 THEN a.atttypmod - 4 ELSE NULL END
				ELSE t.typlen
			END as length,
			
			CASE
			WHEN i.indkey[0] = a.attnum THEN 't'
			WHEN i.indkey[1] = a.attnum THEN 't'
			WHEN i.indkey[2] = a.attnum THEN 't'
			WHEN i.indkey[3] = a.attnum THEN 't'
			WHEN i.indkey[4] = a.attnum THEN 't'
			WHEN i.indkey[5] = a.attnum THEN 't'
			WHEN i.indkey[6] = a.attnum THEN 't'
			WHEN i.indkey[7] = a.attnum THEN 't'
			ELSE 'f'
			END as primary_key,
			(SELECT substring(d.adsrc for 128) FROM pg_catalog.pg_attrdef d	WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as default,
			a.attnotnull, a.attnum
		FROM
			pg_catalog.pg_attribute a
		LEFT JOIN pg_catalog.pg_class c on (a.attrelid=c.oid)
		LEFT JOIN pg_type t on (a.atttypid = t.oid)
		LEFT JOIN pg_index i on (c.oid=i.indrelid)
		WHERE
			a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped
			AND c.relname = '$tablename' AND a.atttypid = t.oid
		ORDER BY a.attnum";
	
		$rs = $this->executeSQL( $sql );
		
		$data = array();
		while($row = pg_fetch_row($rs))
		{
			$name           = $row[0];
			$type_native    = $row[1];

			$type       = preg_replace('@(\(\d+\)|\d+)@','',$row[1]);
			$length     = $row[2] == '' ? null : $row[2];

			$notnull        = $row[5] == 't' ? false : true;
			$primary        = $row[3] == 't' ? true : false;
			$default        = preg_match('@^nextval@i', $row[4]) ? null : $row[4];
			$autoincrement  = preg_match('@^nextval@i', $row[4]) ? true : false;
			
			$data[] = array($name, $type_native, $type, $length, $primary, $notnull, $default, $autoincrement);
		}
		
		return $data;
	}
	
	public function executeSQL($sql)
	{
		$this->dispatchEvent('preExecute', $this, $sql);
		$this->connect();
		$rs = @pg_query($this->conn_id, $sql);
		
		if( ! $rs )
		{
			$msg = $this->getErrorMsg();
			$this->dispatchEvent('onExecuteError', $this, $sql, $msg);
			throw new Lumine_Exception("Falha na consulta: " . $msg."<br>" . $sql, Lumine_Exception::QUERY_ERROR);
		}
		$this->last_rs = $rs;
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
			return sprintf("LIMIT %d OFFSET %d", $limit, $offset);
		}
	}
	
	public function escape($str) 
	{
		$var = pg_escape_string($str);
		return $var;
	}
	
	public function escapeBlob($blob)
	{
		return pg_escape_bytea($blob);
	}
	
	public function affected_rows()
	{
		if(self::$state == self::OPEN && $this->last_rs)
		{
			return pg_affected_rows($this->last_rs);
		}
		throw new Lumine_Exception('Conexão não está aberta', Lumine_Exception::ERRO);
	}
	
	public function num_rows($rs)
	{
		return pg_num_rows($rs);
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