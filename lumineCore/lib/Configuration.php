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
			throw new Lumine_Exception("Dialeto n�o definido na configura��o", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['database']))
		{
			throw new Lumine_Exception("Banco de dados n�o definido na configura��o", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['user']))
		{
			throw new Lumine_Exception("Usu�rio n�o definido na configura��o", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['class_path']))
		{
			throw new Lumine_Exception("Class-path n�o definida na configura��o", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		if(empty($options['package']))
		{
			throw new Lumine_Exception("Pacote n�o definido na configura��o", Lumine_Exception::CONFIG_NO_DIALECT);
			return;
		}
		
		// opcionais, coloca valores padr�es se n�o informados
		if(!isset($options['password']))
		{
			Lumine_Log::debug('Senha n�o definida na configura��o');
			$options['password'] = '';
		}
		if(!isset($options['port']))
		{
			Lumine_Log::debug('Porta n�o definido na configura��o');
			$options['port'] = '';
		}
		if(!isset($options['host']))
		{
			Lumine_Log::debug('Host n�o definido na configura��o');
			$options['host'] = 'localhost';
		}

		$this->options = $options;
		
		$cnManager = Lumine_ConnectionManager::getInstance();
		$cnManager->create($this->options['package'], $this);
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
			Lumine_Log::warning('Op��o inexistente: ' . $name);
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
				Lumine_Log::debug('Classe j� existente: '.$className);
			}
			
			if( file_exists($filename) )
			{
				require_once $filename;
	
				if( ! class_exists($className) )
				{
					throw new Lumine_Exception('A classe '.$className.' n�o existe no arquivo '.$filename);
				}
				
				Lumine_Log::debug('Classe carregada: '.$className);
			} else {
				throw new Lumine_Exception('Arquivo n�o encontrado: '.$filename, Lumine_Log::ERROR);
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