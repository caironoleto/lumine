<?php

/**
 * Interface para exportaчуo de schema (criar as tabelas no banco)
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br
 */

abstract class ILumine_Export {

	protected $tables      = array();
	protected $indexes     = array();
	protected $foreignKeys = array();
	protected $cnn;
	protected $cfg;
	
	protected $fileList = array();
	protected $classList = array();
	
	protected $loaded = false;

	public function export(Lumine_Configuration $cfg)
	{
		$this->cfg = $cfg;
		$this->cnn = $cfg->getConnection();

		$this->create();
	}
	
	/**
	 * Efetua a criaчуo das tabelas no banco
	 * Deve ser especializado pela sub-classe
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */
	protected function create()
	{
	}

	/**
	 * Recupera as definiчѕes de tabelas a serem criadas
	 * Deve ser especializado pela sub-classe
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */	
	protected function getTablesDefinition()
	{
	}
	
	/**
	 * Recupera os indices a serem criados. 
	 * Deve ser especializado pela sub-classe
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */
	protected function getIndexes()
	{
	}
	
	/**
	 * Carrega a lista de arquivos e classes instanciadas da configuraчуo indicada
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */
	protected function loadClassFileList ()
	{
		if( $this->loaded == true )
		{
			return;
		}
		
		$this->loaded = true;
		
		$dir = $this->cfg->getProperty('class_path') . DIRECTORY_SEPARATOR;
		$dir .= str_replace('.', DIRECTORY_SEPARATOR, $this->cfg->getProperty('package'));
		$dir .= DIRECTORY_SEPARATOR;
		
		if( is_dir($dir) )
		{
			$dh = opendir($dir);
			
			while( ($file=readdir($dh)) !== false )
			{
				if( preg_match('@\.php$@', $file) )
				{
					$className = str_replace('.php', '', $file );
					$this->cfg->import( $className );
					
					if( class_exists($className) )
					{
						$oReflection = new ReflectionClass( $className );
						$oClass = $oReflection->newInstance();
						
						if( is_a($oClass, 'Lumine_Base') )
						{
							$this->fileList[] = $dir . $file;
							$this->classList[ $className ] = $oClass;
						} else {
							unset($oClass);
						}
						
						unset($oReflection);
					}
				}
			}
		}
	}

	/**
	 * Este mщtodo sѓ recupera as referencias de chaves estrangeiras, quem vai gerar щ o mщtodo especializado create
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */
	protected function getForeignKeys()
	{
		$this->loadClassFileList();

		$tmp = array();
		
		foreach( $this->classList as $obj )
		{
			$list = $obj->_getForeignRelations();
			
			foreach( $list as $fk )
			{
				if( $fk['type'] == Lumine_Base::MANY_TO_ONE )
				{
					$foreign = $this->classList[ $fk['class'] ];
					$field = $foreign->_getField( $fk['linkOn'] );
					
					$this->foreignKeys[] = array(
						'table' => $obj->tablename(),
						'column' => $fk['column'],
						'reftable' => $foreign->tablename(),
						'refcolumn' => $field['column'],
						'onUpdate' => $fk['onUpdate'],
						'onDelete' => $fk['onDelete']
					);
				}
			}
		}
	}
	
}

?>