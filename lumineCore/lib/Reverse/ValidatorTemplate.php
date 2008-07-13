<?php

Lumine::load('Reverse_ValidatorTemplate');

class Lumine_Reverse_ValidatorTemplate 
{

	private $author       = 'Hugo Ferreira da Silva';
	private $date         = null;
	private $generator    = "Lumine_Reverse";
	private $link         = 'http://www.hufersil.com.br/lumine';
	private $dtd          = 'http://www.hufersil.com.br/lumine/validator.dtd';
	private $conf         = null;
	private $ident        = '    ';
	private $database     = '';
	private $obj          = null;
	
	function __construct(Lumine_Reverse_ClassTemplate $obj, array $conf)
	{
		$this->date = date("Y-m-d");
		$this->conf = $conf;
		$this->obj  = $obj;
		$this->database = $conf['database'];
	}
	
	public function getGeneratedFile()
	{
		$ds = DIRECTORY_SEPARATOR;
		$modelo  = LUMINE_INCLUDE_PATH . "{$ds}lib{$ds}Templates{$ds}validator.xml";
		$props   = array();
		$options = array();
		
		foreach($this->conf as $key => $val)
		{
			if($key == 'options')
			{
				foreach($val as $k => $v)
				{
					$options[] = $this->ident . $this->ident . "'$k' => '$v'";
				}
				continue;
			}
			
			$props[] = $this->ident .  "'$key' => '$val'";
		}
		
		$str_props   = implode(', '.PHP_EOL, $props) . ', '.PHP_EOL;
		$str_options = implode(', '.PHP_EOL, $options);
		
		if(!file_exists($modelo))
		{
			Lumine_Log::error('O arquivo '.$modelo.' não existe');
			exit;
		}
	
		$file = file_get_contents($modelo);
		$file = str_replace('{properties}', $str_props,   $file);
		$file = str_replace('{options}'   , $str_options, $file);
		$file = preg_replace('@\{(\w+)\}@e', '$this->$1', $file);
		
		return $file;
	}
}

?>