<?php

/**
 * Interface para exportação de schema (criar as tabelas no banco)
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br
 */

Lumine::load('Export_IExport');

class Lumine_Export_MySQL extends ILumine_Export {

	/**
	 * Método especializado que irá realizar a criação das tabelas no banco
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 */
	 
	private $foreignKeysSQL = array();
	
	public function create()
	{
		$this->getForeignKeys();
		$this->getIndexes();
		$this->getTablesDefinition();
		
		$sqlList = array_merge( $this->tables, $this->indexes );
		
		// para cada chave estrangeira, criaremos o SQL de geração do Foreign Key
		reset($this->foreignKeys);
		foreach($this->foreignKeys as $fk)
		{
			$sql = sprintf('ALTER TABLE `%s` ADD FOREIGN KEY(`%s`) REFERENCES `%s`(`%s`) ON DELETE %s ON UPDATE %s', $fk['table'], $fk['column'], $fk['reftable'], $fk['refcolumn'], $fk['onDelete'], $fk['onUpdate']);
			$sqlList[] = $sql;
		}
		
		$sqlList = array_merge($sqlList, $this->foreignKeysSQL);
		
		// ok, agora que temos as definições de tabela, vamos criá-las no banco
		// é só executar uma a uma
		foreach( $sqlList as $sql )
		{
			Lumine_Log::debug('Executando a SQL: '. $sql);
			$this->cnn->executeSQL( $sql );
		}
		
	}
	
	/**
	 * Cria as definições (DLL) das tabelas (CREATE TABLE)
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 */
	public function getTablesDefinition()
	{
		reset($this->classList);
		
		$this->tables[] = 'SET FOREIGN_KEY_CHECKS=0';
		
		// criação das tabelas normais
		$mtm = array();
		foreach( $this->classList as $obj )
		{
			$this->tables[] = 'DROP TABLE IF EXISTS `'.$obj->tablename(). '`';
			$tabledef = 'CREATE TABLE `' . $obj->tablename() .'` (' . PHP_EOL;
			
			$list = $obj->_getDefinition();
			foreach( $list as $field )
			{
				$tabledef .= '    ' . $this->getSGBDDefinition( $field );
				
				$tabledef = trim($tabledef) . ',' . PHP_EOL;
			}

			$tabledef = substr(trim($tabledef), 0, -1) . ') TYPE=InnoDB';
			$this->tables[] = $tabledef;
			
			// vamos olhar agora as definições M-T-M
			$relations = $obj->_getForeignRelations();
			foreach( $relations as $relation )
			{
				if( $relation['type'] == Lumine_Base::MANY_TO_MANY && !array_key_exists($relation['table'], $mtm) )
				{
					$mtm[ $relation['table'] ] = 1;
					$this->tables[] = 'DROP TABLE IF EXISTS `'.$relation['table']. '`';
					$tabledef = 'CREATE TABLE `' . $relation['table'] .'` (' . PHP_EOL;
					
					$field = $obj->_getField( $relation['linkOn'] );
					$def = preg_replace('@^`\w+` @', '`'.$relation['column'] . '` ', $this->getSGBDDefinition($field));
					$def = preg_replace('@(AUTO_INCREMENT|PRIMARY KEY)@i', '', $def);
					
					$tabledef .= $def . ','.PHP_EOL;

					$foreign = $this->classList[ $relation['class'] ];
					$foreignRelations = $foreign->_getForeignRelations();
					
					foreach( $foreignRelations as $fr )
					{
						if( $fr['type'] == Lumine_Base::MANY_TO_MANY && $fr['table'] == $relation['table'] )
						{
							$frfield = $foreign->_getField( $fr['linkOn'] );
							$def = preg_replace('@^`\w+` @', '`'.$fr['column'] . '` ', $this->getSGBDDefinition($frfield));
							$def = preg_replace('@(AUTO_INCREMENT|PRIMARY KEY)@i', '', $def);
							
							$tabledef .= $def .',' . PHP_EOL;
							$tabledef .= 'PRIMARY KEY(' . $relation['column'] . ', ' . $fr['column'] . ')) TYPE=InnoDB';
							break;
						}
					}
					
					// coloca a definição da tabela mtm no array
					$this->tables[] = $tabledef;
					
					// agora, vamos criar as chaves estrangeiras
					$sql = "ALTER TABLE `%s` ADD FOREIGN KEY(`%s`) REFERENCES `%s`(`%s`) ON DELETE %s ON UPDATE %s";
					$this->foreignKeysSQL[] = sprintf($sql, $relation['table'], $relation['column'], $obj->tablename(), $field['column'], 'CASCADE', 'CASCADE');
					$this->foreignKeysSQL[] = sprintf($sql, $fr['table'], $fr['column'], $foreign->tablename(), $frfield['column'], 'CASCADE', 'CASCADE');
				}
			}
		}
		
		
	}
	
	public function getSGBDDefinition( $field )
	{
		$def = '`' . $field['column'] . '` ';
		switch( $field['type'] )
		{
			case 'varchar':
				$def .= ' VARCHAR';
				if( !empty($field['length']) )
				{
					$def .= '(' . $field['length'] . ')';
				}
				$def .= ' ';
			break;
			
			case 'text':
				if( $field['length'] == 255 ) $def .= ' TINYTEXT ';
				if( $field['length'] == 65535 ) $def .= ' TEXT ';
				if( $field['length'] == 16777215 ) $def .= ' MEDIUMTEXT ';
				if( $field['length'] == 4294967295 ) $def .= ' LONGTEXT ';
				if( empty($field['length']) ) $def .= ' TEXT ';
			break;
			
			case 'blob':
				if( $field['length'] == 255 ) $def .= ' TINYBLOB ';
				if( $field['length'] == 65535 ) $def .= ' BLOB ';
				if( $field['length'] == 16777215 ) $def .= ' MEDIUMBLOB ';
				if( $field['length'] == 4294967295 ) $def .= ' LONGBLOB ';
				if( empty($field['length']) ) $def .= ' BLOB ';
			break;
			
			case 'boolean':
				$def .= 'BOOL ';
			break;
			
			case 'int':
				$def .= ' INT';
				if( !empty($field['length']) )
				{
					$def .= '(' . $field['length'] . ')';
				}
				$def .= ' ';
			break;
			
			default: 
				$def .= ' ' . strtoupper($field['type']);
				if( !empty($field['length']) )
				{
					$def .= '(' . $field['length'] . ')';
				}
				$def .= ' ';
			break;
		}
		
		if( !empty($field['options']['notnull']) ) $def .= 'NOT NULL ';
		if( !empty($field['options']['primary']) ) $def .= 'PRIMARY KEY ';
		if( !empty($field['options']['autoincrement']) ) $def .= 'AUTO_INCREMENT ';
		
		return $def;
	}
	
	/**
	 * Cria os comandos SQL para gerar os índices
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return void
	 */
	public function getIndexes()
	{
		reset($this->foreignKeys);
		foreach($this->foreignKeys as $fk)
		{
			$sql = sprintf('CREATE INDEX `%1$s_%2$s_%3$s` ON `%2$s`(`%3$s`)', 'idx', $fk['table'], $fk['column']);
			$this->indexes[] = $sql;
		}
		
	}

	
}

?>