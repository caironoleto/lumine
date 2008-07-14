<?php

Lumine::load('Log');

class Lumine_Configuration extends Lumine_EventListener
{

	public $options;
	private $connection = null;
	
	function __construct(array $options)
	{
		if(empty($options['dialect']))
		{
			throw new Lumine_Exception("Dialeto não definido na configuração", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['database']))
		{
			throw new Lumine_Exception("Banco de dados não definido na configuração", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['user']))
		{
			throw new Lumine_Exception("Usuário não definido na configuração", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['class_path']))
		{
			throw new Lumine_Exception("Class-path não definida na configuração", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['package']))
		{
			throw new Lumine_Exception("Pacote não definido na configuração", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		
		// opcionais, coloca valores padrões se não informados
		if(!isset($options['password']))
		{
			Lumine_Log::debug('Senha não definida na configuração');
			$options['password'] = '';
		}
		if(!isset($options['port']))
		{
			Lumine_Log::debug('Porta não definido na configuração');
			$options['port'] = '';
		}
		if(!isset($options['host']))
		{
			Lumine_Log::debug('Host não definido na configuração');
			$options['host'] = 'localhost';
		}

		$this->options = $options;
		
		$this->setConnection(Lumine_ConnectionManager::getInstance()->create($this->options['package'], $this));
	}
	
	public function setConnection(ILumine_Connection $conn)
	{
		$this->connection = $conn;
	}
	
	public function getConnection()
	{
		return $this->connection;
	}
	
	public function getProperty( $name )
	{
		if( ! isset($this->options[ $name ]) ) 
		{
			Lumine_Log::warning('Propriedade inexistente: ' . $name);
			return null;
		}
		
		return $this->options[ $name ];
	}
	
	public function getOption( $name )
	{
		if( ! isset($this->options['options'][ $name ]) ) 
		{
			Lumine_Log::warning('Opção inexistente: ' . $name);
			return null;
		}
		
		return $this->options['options'][ $name ];
	}
	
	public function import() 
	{
		$list = func_get_args();
		
		foreach($list as $className)
		{
			$arr_path = explode('.', $this->getProperty('package'));
			$ps = DIRECTORY_SEPARATOR;
			$path = $this->getProperty('class_path') . $ps . implode($ps, $arr_path) . $ps;
			
			$sufix = $this->getOption('class_sufix');
			
			if($sufix != null)
			{
				$sufix = '.' . $sufix;
			}
			
			$sufix = $sufix . '.php';
			$filename = $path . $className . $sufix;
			
			if( class_exists($className) )
			{
				Lumine_Log::debug('Classe já existente: '.$className);
			}
			
			if( file_exists($filename) )
			{
				require_once $filename;
	
				if( ! class_exists($className) )
				{
					throw new Lumine_Exception('A classe '.$className.' não existe no arquivo '.$filename);
				}
				
				Lumine_Log::debug('Classe carregada: '.$className);
			} else {
				throw new Lumine_Exception('Arquivo não encontrado: '.$filename, Lumine_Log::ERROR);
			}
		}
	}
	
	public function export() 
	{
		$class = 'Lumine_Export_' . $this->options['dialect'];
		Lumine::load( $class );
		
		$reflection = new ReflectionClass( $class );
		$instance = $reflection->newInstance();
		$instance->export( $this );
	}
	
}


?>
