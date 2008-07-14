<?php

/**
 * Classe de gerenciamento de conexão com o banco de dados
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br HUFERSIL.WEBDEVELOPER
 *
 */

class Lumine_ConnectionManager extends Lumine_EventListener
{
	private static $instance = null;
	private $connections = array();
	
	public static function getInstance()
	{
		if(self::$instance == null)
		{
			self::$instance = new Lumine_ConnectionManager;
		} 
		
		return self::$instance;
	}
	
	/**
	 * Cria uma nova referencia de conexao com o banco
	 *
	 * @param string $connectionName Nome da conexao
	 * @param Lumine_Configuration $config Objeto de configuracao
	 * @return void
	 */
	public function create($connectionName, Lumine_Configuration $config)
	{
		$connObj = $this->getConnection($connectionName);
		if( $connObj != false )
		{
			Lumine_Log::warning('Já existe uma conexão com este nome: ' .$connectionName );
		} else {
			Lumine_Log::debug('Armazenando conexão: ' .$connectionName);
			
			$connObj = $this->getConnectionClass( $config->options['dialect'] );
			
			if($connObj == false)
			{
				Lumine_Log::error( 'Dialeto não implementado: ' .$config->options['dialect']);
				return;
			}
			
			$connObj->setDatabase( $config->options['database'] );
			$connObj->setHost( $config->options['host'] );
			$connObj->setPort( $config->options['port'] );
			$connObj->setUser( $config->options['user'] );
			$connObj->setPassword( $config->options['password'] );
			
			if(isset($config->options['options']))
			{
				$connObj->setOptions( $config->options['options'] );
			}
			
			$config->setConnection( $connObj );
			$this->connections[ $connectionName ] = $config;
		}
		return $connObj;
	}
	
	/**
	 * Recupera uma conexao com o nome informado
	 *
	 * @param string $connectionName Nome da conexao desejada
	 * @return Lumine_Configuration Configuracao / conexao encontrada ou false se nao recuperar
	 */
	public function getConnection( $connectionName ) 
	{
		if( ! isset($this->connections[ $connectionName ]))
		{
			Lumine_Log::warning('Conexão inexistente: ' .$connectionName);
			return false;
		}
		return $this->connections[ $connectionName ]->getConnection();
	}
	
	//Carrega a classe do DIALETO
	public function getConnectionClass( $dialect ) 
	{
		$file = LUMINE_INCLUDE_PATH . '/lib/Connection/'.$dialect.'.php';
		if(file_exists($file) == false)
		{
			throw new Lumine_Exception('Tipo de conexão inexistente: ' .$connectionName, Lumine_Exception::ERROR);
		}
		
		$class_name = 'Lumine_Connection_' . $dialect;
		
		require_once $file;
		$obj = new $class_name;
		return $obj;
	}
	
	public function getConfiguration( $name )
	{
		if( ! isset($this->connections[ $name ])) 
		{
			throw new Lumine_Exception('Configuração inexistente: ' .$name, Lumine_Exception::WARNING);
		}
		
		return $this->connections[ $name ];
	}
	
	public function getConfigurationList()
	{
		return $this->connections;
	}
	
	function __destruct()
	{
	    unset($this->instance);
	    parent::__destruct();
	}
}
?>
