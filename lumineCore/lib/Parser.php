<?php


class Lumine_Parser {
	
	public static function parsePart(Lumine_Base $obj, $str, $args = null)
	{
		$total = strlen($str);
		$inStr = false;
		$start = '';
		$nova = '';
		$tmp = '';
		
		for($i=0; $i<$total; $i++) 
		{
			$c = substr($str, $i, 1);
			
			if($inStr == false && ($c == "'" || $c == '"'))
			{
				$tmp = self::parseEntityValues($obj, $tmp, $args);
				$nova .= $tmp;
				$tmp = '';
				$inStr = true;
				$start = $c;
				continue;
			}
			
			if($inStr == true && $c == $start)
			{
				$tmp_test = str_replace( $obj->_getConnection()->getEscapeChar() . $start, '', $c . $tmp . $c);
				if( substr_count($tmp_test, "'") % 2 == 0 )
				{
					$nova .= $start . $tmp . $start;
					$inStr = false;
					$tmp = '';
					$start = '';
					continue;
				}
			}
			
			$tmp .= $c;
		}
		
		if($tmp != '')
		{
			if($inStr == true)
			{

			}
			$nova .= self::parseEntityValues($obj, $tmp, $args);
		}
		
		return $nova;
	}
	
	public static function parseEntityValues($obj, $str, $args = null)
	{
		
		// vamos procurar por campos em que o usuário usou alias
		// por exemplo: c.nome = ? c.idade = ?
		// onde "c" seria o alias de Cliente, alterado com o método $obj->setAlias( 'c' );
		// faremos isto em cada entidade listada
		$list = $obj->_getObjectPart('_join_list');
		$cnn = $obj->_getConnection();
		$idx = 0;
		
		// encontrará:
		// - alias.nome_campo
		// - {Classe.propriedade}
		// - {propriedade}
		preg_match_all('@((\w+)\.(\w+)|\{(\w+)\.(\w+)\}|\{(\w+)\})\s*(=|<=|>=|like|\!=|<>|>|<|ilike|not like|in|not in)\s*\(?\s*(\?|:\w+)\)?@i', $str, $reg);
		$total = count($reg[0]);
		
		for($i=0; $i<$total; $i++)
		{
			// pega o valor como "prepared statement"
			if( $reg[8][$i] == '?' )
			{
				$val = empty($args[$i]) ? '' : $args[$i];
			} else {
				$p = substr($reg[8][$i], 1);
				$val = empty($args[0][$p]) ? '' : $args[0][$p];
			}
			
			/*
			 * Primeiro, vamos ver se é uma clausula IN ou NOT IN
			 * se for, vamos avaliar se o parametro passado é um ARRAY, LUMINE_BASE ou qualquer outro valor
			 */
			$operacao = strtolower( $reg[7][$i] );
			
			if( in_array($operacao, array('in','not in')) )
			{
				// recuperamos o campo
				if( (!empty($reg[2][$i]) && !empty($reg[3][$i])) || !empty($reg[4][$i]) )					// informou o alias ou nome da classe
				{
					foreach($list as $entity)								// para cada entidade
					{
						$name = $entity->_getName();						// pega o nome da entidade
						
						if($entity->_getAlias() == $reg[2][$i])				// encontrou pelo alias
						{
							$field = $entity->_getField( $reg[3][$i] );		// pega o campo
							break;											// para o loop
						}
						
						if($name == $reg[4][$i])							// informou o nome da classe
						{
							$field = $entity->_getField( $reg[5][$i] );		// pega o campo
							break;											// pára o loop
						}
					}
				} else if( !empty($reg[6][$i]) ) {							// mas se informou somente o campo como {campo}
					$entity = $obj;											// indica o objeto
					$field = $entity->_getField( $reg[6][$i] );				// pega o campo da classe

				} else {													// sei lá o que ele fez
					continue;												// passa para o proximo e não faz nada
				}
		
				// sim, é um in ou not in
				// vamos ver o tipo de dados passado
				
				// se for um array
				if( is_array($val) && !empty($val) )
				{
					// percorremos os itens para ver se é string ou numero
					foreach( $val as $chave => $valor )
					{
						$valor = $cnn->escape($valor);
						$valor = self::getParsedValue($entity, $valor, $field['type'], false);
						$val[ $chave ] = $valor;
					}

					// agora que está tudo certo, vamos substituir o valor
					$lista = implode(', ', $val);
					//$lista = '(' . $lista . ')';
					$str = substr_replace($str, str_replace( $reg[8][$i] , $lista, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));
				
				} else if( is_a($val, 'Lumine_Base') ) {					// mas se for uma instancia de Lumine_Base
					// se a consulta retornou maior que zero
					if( $val->numrows() > 0 )
					{
						$lista = array();
						$result = $val->allToArray();
						$key = key($result[0]);
						
						foreach($result as $row)
						{
							$valor = $row[ $key ];
							$valor = self::getParsedValue($entity, $valor, $field['type'], false);
							$lista[] = $valor;
						}
						
						$str_lista = implode(', ', $lista);
					
					} else {
						$str_lista = 'null';
					}

					$str = substr_replace($str, str_replace( $reg[8][$i] , $str_lista, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));
				
				} else {													// se for qualquer outro valor
					$valor = self::getParsedValue($entity, $val, $field['type'], false);
					$str = substr_replace($str, str_replace( $reg[8][$i] , $valor, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));
				}
				
				// passa para o proximo parametro
				continue;
			}

			if( ! empty($reg[2][$i]) && ! empty($reg[3][$i]) ) // encontrou pelo alias
			{
				foreach($list as $entity)
				{
					$name = $entity->_getName();
					if($entity->_getAlias() == $reg[2][$i])
					{
						$field = $entity->_getField( $reg[3][$i] );
						$val = self::getParsedValue($entity, $val, $field['type'], strpos(strtolower($reg[7][$i]), 'like') !== false);
						
						$str = substr_replace($str, str_replace( $reg[8][$i] , $val, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));
						
						reset($list);
						break;
					}
				}
				continue;
			}
			
			if( ! empty($reg[4][$i]) && ! empty($reg[5][$i]) ) // encontrou por {Classe.prop}
			{
				$name = $reg[4][$i];
				
				foreach($list as $ent)
				{
					if($name == $ent->_getName())
					{
						$entity = $ent;
						break;
					}
				}

				$field = $entity->_getField( $reg[5][$i] );

				$val = self::getParsedValue($entity, $val, $field['type'], strpos(strtolower($reg[7][$i]), 'like') !== false);

				$str = substr_replace($str, str_replace( $reg[8][$i] , $val, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));
			
				continue;
			}
			
			if( ! empty($reg[6][$i]) ) // encontrou por {prop}
			{
				$entity = $obj; 
				$field = $entity->_getField( $reg[6][$i] );

				$val = self::getParsedValue($entity, $val, $field['type'], strpos(strtolower($reg[7][$i]), 'like') !== false);
				
				$str = substr_replace($str, str_replace($reg[8][$i], $val, $reg[0][$i]), strpos($str, $reg[0][$i]), strlen($reg[0][$i]));

			
				continue;
			}
		}
		
		return $str;
	}
	
	
	public static function getParsedValue($obj, $val, $type, $islike = false)
	{
		if( is_null( $val ) == true )
		{
			return 'NULL';
		}
		switch($type)
		{
			case 'int':
			case 'integer':
				$val = sprintf('%d', $val);
				break;
		
			case 'float':
			case 'double':
				$val = sprintf('%f', $val);
				break;
			
			case 'date':
				/*
				if(is_numeric($val))
				{
					$val = "'" . date('Y-m-d', $val) . "'";
				} else {
					$val = "'" . date('Y-m-d', strtotime($val)) . "'";
				}*/
				$val = "'" . Lumine_Util::FormatDate( $val, '%Y-%m-%d' ) . "'";
				break;
			
			case 'datetime':
				/*
				if(is_numeric($val))
				{
					$val = "'" . date('Y-m-d H:i:s', $val) . "'";
				} else {
					$val = "'" . date('Y-m-d H:i:s', strtotime($val)) . "'";
				}
				*/
				$val = "'" . Lumine_Util::FormatDateTime( $val, '%Y-%m-%d %H:%M:%S' ) . "'";
				break;
				
			case 'time':
			    $val = Lumine_Util::FormatTime($val, '%H:%M:%S');
			    $val = "'" . $val . "'";
			    break;

			case 'boolean':
				$val = sprintf('%d', $val);
				break;
			
			case 'string':
			case 'text':
			case 'varchar':
			case 'char':
			default:
				if( $islike == true)
				{
					$val = "'%" . $obj->_getConnection()->escape( $val ) . "%'";
				} else {
					$val = "'" . $obj->_getConnection()->escape( $val ) . "'";
				}
				break;
		}
		
		return $val;
	}
	
	public static function parseSQLValues($obj, $str)
	{
		$total = strlen($str);
		$inStr = false;
		$start = '';
		$nova = '';
		$tmp = '';
		
		for($i=0; $i<$total; $i++) 
		{
			$c = substr($str, $i, 1);
			
			if($inStr == false && ($c == "'" || $c == '"'))
			{
				$tmp = self::parseEntityNames($obj, $tmp);
				$nova .= $tmp;
				$tmp = '';
				$inStr = true;
				$start = $c;
				continue;
			}
	
/*			if($inStr == true && $c == $start && substr($str, $i-1, 1) != '\\' && $c != '\\')
			{
				$nova .= $start . $tmp . $start;
				$inStr = false;
				$tmp = '';
				$start = '';
				continue;
				*/
			
			if($inStr == true && $c == $start)
			{
				
				$tmp_test = str_replace( $obj->_getConnection()->getEscapeChar() . $start, '', $c . $tmp . $c);
				
				//if( !substr_count($tmp_test, "'") & 1 )
				if( substr_count($tmp_test, "'") % 2 == 0 )
				{
					$nova .= $start . $tmp . $start;
					$inStr = false;
					$tmp = '';
					$start = '';
					continue;
				}
			}
			
			$tmp .= $c;
		}
		
		if($tmp != '')
		{
			if($inStr == true)
			{
				$tmp = $start . $tmp;
			}
			$nova .= self::parseEntityNames($obj, $tmp);
		}
		
		return $nova;
	}
	
	public static function parseFromValue( $obj )
	{
		$schema = $obj->_getConfiguration()->getOption('schema_name');
		if( empty($schema) )
		{
			$name = $obj->tablename();
		} else {
			$name = $schema .'.'. $obj->tablename();
		}
		
		if($obj->_getAlias() != '') 
		{
			$name .= ' '.$obj->_getAlias();
		}
		return $name;
	}
	
	public static function parseJoinValues($obj, $list)
	{
		$joinStr = implode("\r\n", $obj->_getObjectPart('_join'));
		
		preg_match_all('@(\{(\w+)\.(\w+)\})@', $joinStr, $reg);
		$total = count($reg[0]);
		
		$schema = $obj->_getConfiguration()->getOption('schema_name');
		if( !empty($schema)) 
		{
			$schema .= '.';
		}
		
		for($i=0; $i<$total; $i++)
		{
			if( !empty($reg[2][$i]) && !empty($reg[3][$i])) // exemplo: {Usuario.idusuario}
			{
				// alterado em 28/08/2007
				foreach($list as $ent)
				{
					if($ent->_getName() == $reg[2][$i])
					{
						// $ent = $list[ $reg[2][$i] ];
						$field = $ent->_getField( $reg[3][$i] );
						$name = $ent->tablename();
						$a = $ent->_getAlias();
						
						if( !empty($a) )
						{
							$name = $a;
						}
						
						$joinStr = str_replace($reg[0][$i], $name . '.' .$field['column'], $joinStr);
					}
				}
				
				/*
				if( !empty( $list[ $reg[2][$i] ]))
				{
					$ent = $list[ $reg[2][$i] ];
					$field = $ent->_getField( $reg[3][$i] );
					$name = $ent->tablename();
					$a = $ent->_getAlias();
					
					if( !empty($a) )
					{
						$name = $a;
					}
					
					$joinStr = str_replace($reg[0][$i], $name . '.' .$field['column'], $joinStr);
				}
				*/
			}
		}
		
		preg_match_all('@JOIN (\{(\w+)\})@i', $joinStr, $reg);
		$total = count($reg[0]);
		
		for($i=0; $i<$total; $i++)
		{
			if( !empty($reg[2][$i])) // exemplo: (INNER|LEFT|RIGHT) JOIN {Grupo}
			{
				reset($list);
				
				foreach($list as $ent)
				{
					if($ent->_getName() == $reg[2][$i])
					{
						break;
					}
				}
				// $ent = $list[ $reg[2][$i] ];
				$joinStr = str_replace($reg[0][$i], 'JOIN '. $schema . $ent->tablename() .' ' . $ent->_getAlias(), $joinStr);
			}
		}
		
		return "\r\n".$joinStr;
	}
	
	public static function parseEntityNames($obj, $str)
	{
		
		// fazer parse de u.nome (alias + . + nome_do_campo) de cada entidade
		$list = $obj->_getObjectPart('_join_list');
		
		foreach($list as $ent)
		{
			$a = $ent->_getAlias();
			$name = $ent->_getName();
			
			if( !empty($a))
			{
				preg_match_all('@\b'.$a.'\b\.(\w+)@', $str, $reg);
				$total = count($reg[0]);
				
				for($i=0; $i<$total; $i++) 
				{
					$field = $ent->_getField( $reg[1][$i] );
					$str = str_replace($reg[0][$i], $a . '.' . $field['column'], $str);
				}
			}
			
			preg_match_all('@\{'.$name.'\.(\w+)\}@', $str, $reg);
			$total = count($reg[0]);
			
			for($i=0; $i<$total; $i++) 
			{
				$field = $ent->_getField( $reg[1][$i] );
				
				if( !empty($a))
				{
					$str = str_replace($reg[0][$i], $a . '.' . $field['column'], $str);
				} else {
					$str = str_replace($reg[0][$i], $ent->tablename() . '.' . $field['column'], $str);
				}
			}
		}
		
		
		// encontra por {propriedade}
		// quando não especificado, significa que pertence a mesma entidade
		// chamadora da função, por isso não fazemos loop
		
		preg_match_all('@\{(\w+)\}@', $str, $reg);
		$total = count($reg[0]);
		
		for($i=0; $i<$total; $i++)
		{
			$f = $obj->_getField($reg[1][$i]);
			$a = $obj->_getAlias();
			
			if($a == '')
			{
				$a = $obj->tablename();
			}
			
			$str = str_replace($reg[0][$i], $a . '.'. $f['column'], $str);
		}
		
		return $str;
	}
}


?>