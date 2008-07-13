<?php

/**
 * Classe para efetuar a engenharia reversa
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br HUFERSIL
 * @license http://www.gnu.org/licenses/lgpl.html LPGL
 */

Lumine::load('Reverse_ClassTemplate','Reverse_ConfigurationTemplate');
Lumine::load('Utils_dZip.inc');

class Lumine_Reverse extends Lumine_EventListener
{

	private $many_to_many         = array();
	private $tables               = array();
	private $cfg                  = null;
	private $classes              = array();
	private $files                = array();
	private $controls             = array();
	private $config               = '';
	private $original_options     = array();
	private $dialect              = null;
	
	public function __construct(array $options)
	{
		$this->cfg = new Lumine_Configuration( $options );
		$this->original_options = $options;
	}

	public function start()
	{
		Lumine_Log::debug('Iniciando engenharia reversa');
		$cfg = $this->cfg;
		$dbh = $cfg->getConnection();
		$dbh->connect();
		
		$dialect = $cfg->getProperty('dialect');
		$class_dialect = 'Lumine_Dialect_'.$cfg->getProperty('dialect');
		Lumine::load($class_dialect);
		
		$this->dialect = new $class_dialect(null);

		if(empty($this->tables))
		{
			$this->tables = $dbh->getTables();
		}

		$many_to_many = array();
		$mtm_style = $cfg->getOption('many_to_many_style');
		if( empty($mtm_style))
		{
			$mtm_style = '%s_%s';
		}
		
		for($i=0; $i<count($this->tables); $i++) {
			for($j=0; $j<count($this->tables); $j++) {
				$rel = sprintf($mtm_style, $this->tables[$i], $this->tables[$j]);

				if(in_array($rel, $this->tables)) {
					if(!array_key_exists($rel, $many_to_many)) {
						$many_to_many[] = $rel;
					}
					continue;
				}
				
			}
		}
		
		$camel = $cfg->getOption('camel_case');
		foreach($this->tables as $table)
		{
			if(in_array($table, $many_to_many) && $cfg->getOption('create_entities_for_many_to_many') != true)
			{
				continue;
			}
			
			Lumine_Log::debug('Analisando tabela '.$table);
			$classname = $table;
			if( $cfg->getOption('remove_prefix'))
			{
				Lumine_Log::debug('Removendo prefixo da tabela '.$table);
				$classname = preg_replace('@^'.$cfg->getOption('remove_prefix').'@', '', $classname);
			}
			if( $cfg->getOption('remove_count_chars_start') > 0)
			{
				Lumine_Log::debug('Removendo os primeiros '.$cfg->getOption('remove_count_chars_start') . ' caracteres de '.$table);
				$classname = substr($classname, $cfg->getOption('remove_count_chars_start'));
			}
			if( $cfg->getOption('remove_count_chars_end'))
			{
				Lumine_Log::debug('Removendo os últimos '.$cfg->getOption('remove_count_chars_start') . ' caracteres de '.$table);
				$classname = substr($classname, 0, strlen($classname) - $cfg->getOption('remove_count_chars_end'));
			}
			
			$classname = ucfirst(strtolower($classname));
			
			$field_list = $dbh->describe( $table );

			Lumine_Log::debug('Criando entidade reversa de '.$table);
			$obj = new Lumine_Reverse_ClassTemplate($table, $classname, $cfg->getProperty('package'));
			
			Lumine_Log::debug('Recuperando chaves estrangeiras de '.$table);
			$obj->setForeignKeys( $dbh->getForeignKeys( $table ) );
			
			Lumine_Log::debug('Recuperando os campos de '.$table);
			$obj->setDescription( $field_list );
			
			$obj->setDialect( $this->dialect );
			
			$obj->setCamelCase( ! empty($camel) );
			
			$this->classes[ $table ] = $obj;
		}

		$this->many_to_many = $many_to_many;
		unset($many_to_many);

		$this->checkRerentialIntegrity();
		$this->createFiles();

		$controls  = $cfg->getOption('create_controls');		
		$to_zip    = $cfg->getOption('generate_zip');
		$to_files  = $cfg->getOption('generate_files');
		$overwrite = $cfg->getOption('overwrite');
		
		$this->createConfigurationsFile();
		
		if( !empty($controls))
		{
			$this->createControls( $controls );
		}
		
		if( !empty($to_zip))
		{
			$this->generateZip();
		}
		if( !empty($to_files))
		{
			$this->generateFiles($overwrite);
		}
	}
	
	public function setTables( array $list)
	{
		$this->tables = $list;
	}
	
	private function checkRerentialIntegrity()
	{
		$cfg = $this->cfg;
		$dbh = $cfg->getConnection();
		
		// gera as referencias de cada classe
		foreach($this->classes as $tablename => $obj)
		{
			$fks = $obj->getForeignKeys();
			foreach($fks as $from => $def)
			{
				$defx = $obj->getDefColumn( $def['from'] );
				
				if( empty($this->classes[$def['to']]))
				{
					Lumine_Log::error('Erro na integridade referencial de '.$tablename .' para '. $def['to']);
					exit;
				}
				
				$colNameTo = $this->cfg->getOption('keep_foreign_column_name') == true ? $def['to_column'] : $this->classes[ $def['to'] ]->getClassname();
				
				if( $this->cfg->getOption('keep_foreign_column_name') == true )
				{				
					$defx[0] = $def['from'];
				} else {
					$defx[0] = strtolower($this->classes[ $def['to'] ]->getClassname());
				}
				
				$defx['options'] = array(
					'column'   => $def['from'],
					'foreign'  => true,
					'onUpdate' => $def['update'],
					'onDelete' => $def['delete'],
					'linkOn'   => $colNameTo,
					'class'    => $this->classes[ $def['to'] ]->getClassname()
				);
				
				$obj->setDefColumn( $def['from'], $defx);
				
				
				$rel = array(
					'class'     => $obj->getClassname(),
					'linkOn'    => $defx[0],
					'name'      => $this->toPlural( $obj->getClassname() )
				);
				
				$this->classes[ $def['to'] ]->addOneToMany( $rel );
			}
		}
		
		// gera as referencias many-to-many
		foreach($this->many_to_many as $mtm)
		{
			$fks = $dbh->getForeignKeys( $mtm );
			$keys = array_keys($fks);
			
			$fk_1 = $fks[ $keys[0] ];
			$fk_2 = $fks[ $keys[1] ];
			
			$col_def_1 = $this->classes[ $fk_1['to'] ]->getDefColumn( $fk_1['from'] );
			$col_def_2 = $this->classes[ $fk_2['to'] ]->getDefColumn( $fk_2['from'] );
			
			$def_1 = array(
				'name'        => $this->toPlural( $this->classes[ $fk_1['to'] ]->getClassname() ),
				'class'       => $this->classes[ $fk_1['to'] ]->getClassname(),
				'linkOn'      => $col_def_2[0],
				'type'        => 'MANY_TO_MANY',
				'table_join'  => $mtm,
				'column_join' => $fk_2['from'],
				'lazy'        => 'null'
			);

			$def_2 = array(
				'name'        => $this->toPlural( $this->classes[ $fk_2['to'] ]->getClassname() ),
				'class'       => $this->classes[ $fk_2['to'] ]->getClassname(),
				'linkOn'      => $col_def_1[0],
				'type'        => 'MANY_TO_MANY',
				'table_join'  => $mtm,
				'column_join' => $fk_1['from'],
				'lazy'        => 'null'
			);

			$this->classes[ $fk_1['to'] ]->addManyToMany( $def_2 );
			$this->classes[ $fk_2['to'] ]->addManyToMany( $def_1 );
		}
	}
	
	private function createFiles()
	{
		reset($this->classes);
		
		foreach($this->classes as $table => $obj)
		{
			Lumine_Log::debug('Gerando arquivo para '.$obj->getClassname());
			$this->files[ $obj->getClassname() ] = $obj->getGeneratedFile();
		}
	}
	
	private function toPlural( $name )
	{
		$pl = $this->cfg->getOption('plural');
		if( !empty($pl))
		{
			$name .= $pl;
		}
		return strtolower($name);
	}
	
	
	private function generateZip()
	{
		Lumine_Log::debug('Gerando arquivo ZIP');
		$raiz = $this->cfg->getProperty('class_path') . '/';
		$zipname = $this->cfg->getProperty('class_path') .'/lumine.zip';
		
		if( !is_writable($raiz))
		{
			Lumine_Log::error('Não é possível criar arquivos em "'.$raiz.'". Verifique as permissões.');
			exit;
		}
		
		$zip = new dZip($zipname);
		$sufix = $this->cfg->getOption('class_sufix');
		if( !empty($sufix))
		{
			$sufix = '.' .$sufix;
		}
		$filename = str_replace('.', DIRECTORY_SEPARATOR, $this->cfg->getProperty('package'));
		$filename .= DIRECTORY_SEPARATOR;
		
		reset($this->files);
		foreach($this->files as $classname => $content)
		{
			Lumine_Log::debug('Adicionando '.$classname . ' ao ZIP');
			$name = $filename . $classname . $sufix . '.php';
			$zip->addFile($content, $name, 'Lumine Reverse', $content);
		}
		
		// adiciona os controles
		$path = 'controls' . DIRECTORY_SEPARATOR;
		foreach($this->controls as $classname => $content)
		{
			Lumine_Log::debug('Adicionando controle '.$classname . ' ao ZIP');
			$name = $path . $classname . '.php';
			$zip->addFile($content, $name, 'Lumine Reverse Control', $content);
		}
		
		$zip->addFile($this->config, 'lumine-conf.php', 'Configuration File', $this->config);
		$zip->save();
		// altera as permissões do arquivo
		chmod($zipname, 0777);
		
		Lumine_Log::debug('Arquivo ZIP gerado com sucesso em '.$zipname);
		
		/*
		$fp = @fopen($zipname, "wb+");
		if($fp)
		{
			fwrite($fp, $zip->getZippedfile());
			fclose($fp);
			
			chmod($zipname, 0777);
			
			Lumine_Log::debug('Arquivo ZIP gerado com sucesso em '.$zipname);
		} else {
			Lumine_Log::error('Falha ao gerar ZIP em '.$obj->getClassname().'. Verifique se a pasta existe e se tem direito de escrita.');
			exit;
		}
		*/
		
	}
	
	private function generateFiles( $overwrite )
	{
		Lumine_Log::debug('Gerando arquivos direto na pasta');
		$fullpath = $this->cfg->getProperty('class_path') . DIRECTORY_SEPARATOR.str_replace('.',DIRECTORY_SEPARATOR,$this->cfg->getProperty('package'));
		$sufix = $this->cfg->getOption('class_sufix');
		if( !empty($sufix))
		{
			$sufix = '.' . $sufix;
		}

		$dummy = new Lumine_Reverse_ClassTemplate();
		$end   = $dummy->getEndDelim();
		
		reset($this->files);
		foreach($this->files as $classname => $content)
		{
			$filename = $fullpath . DIRECTORY_SEPARATOR . $classname . $sufix . '.php';
			
			if(file_exists($filename) && empty($overwrite))
			{
				$fp = fopen($filename, 'r');
				$old_content = fread($fp, filesize($filename));
				fclose($fp);
				
				$start       = strpos($old_content, $end) + strlen($end);
				
				$customized  = substr($old_content, $start);
				$top         = substr($content, 0, strpos($content, $end));
				
				$content     = $top . $end . $customized;
			}
			
			$fp = @fopen($filename, 'w');
			if($fp)
			{
				fwrite($fp, $content);
				fclose($fp);
				chmod($filename, 0777);
				
				Lumine_Log::debug('Arquivo para a classe '.$classname .' gerado com sucesso');
			} else {
				Lumine_Log::error('O PHP não tem direito de escrita na pasta "'.$fullpath . '". Verifique se o diretório existe e se o PHP tem direito de escrita.');
				exit;
			}
		}
		
		// escreve os controles
		$path = $this->cfg->getProperty('class_path');
		$path .= DIRECTORY_SEPARATOR . 'controls' . DIRECTORY_SEPARATOR;
		foreach($this->controls as $classname => $content)
		{
			$filename = $path . $classname . '.php';
			$fp = @fopen($filename, 'w');
			if(! $fp)
			{
				Lumine_Log::error('O PHP não tem direito de escrita para gerar o arquivo "'.$filename . '". Verifique se o diretório existe e se o PHP tem direito de escrita.');
				exit;
			} else {
				fwrite($fp, $content);
				fclose($fp);
				Lumine_Log::debug('Arquivo de controle "'.$filename . '" gerado com sucesso.');
			}
		}

		// escreve o arquivo de configuração
		$filename = $this->cfg->getProperty('class_path').DIRECTORY_SEPARATOR.'lumine-conf.php';
		
		$fp = @fopen($filename, 'w');
		if(!$fp)
		{
			Lumine_Log::error('O PHP não tem direito de escrita para gerar o arquivo "'.$filename . '". Verifique se o diretório existe e se o PHP tem direito de escrita.');
			exit;
		}
		
		fwrite($fp, $this->config);
		fclose($fp);
		Lumine_Log::debug('Arquivo "'.$filename . '" gerado com sucesso.');
		
	}
	
	private function createConfigurationsFile()
	{
		$cfg = new Lumine_Reverse_ConfigurationTemplate( $this->original_options );
		$this->config = $cfg->getGeneratedFile();
	}
	
	private function createControls( $controlName )
	{
		$clname = 'Lumine_Form_'.$controlName;
		Lumine::load('Form_'.$controlName);
		
		$clControls = new $clname( null );
		
		reset($this->files);
		foreach($this->files as $classname => $content)
		{
			$this->controls[ $classname ] = $clControls->getControlTemplate($this->cfg, $classname);
		}
		
	}

}

?>