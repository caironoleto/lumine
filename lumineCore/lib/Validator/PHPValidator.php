<?php
/**
* Classe para fazer valida��o em PHP
* @author Cairo Lincoln de Morais Noleto
* @link http://caironoleto.wordpress.com
* @author Hugo Ferreira da Silva
* @link http://www.hufersil.com.br
*/

require_once LUMINE_INCLUDE_PATH . '/lib/Validator/Custom/ValidateCPF.php';
require_once LUMINE_INCLUDE_PATH . '/lib/Validator/Custom/ValidateCNPJ.php';

class Lumine_Validator_PHPValidator
{
	function __construct(Lumine_Base $obj)
	{
		$this->obj = $obj;
	}
	
	/**
	 * Objeto para manter os membros que ter�o que ser validados
	 * @author Hugo Ferreira da Silva
	 */
	protected static $validateList = array();

	/**
	 * Adiciona um membro para a valida��o
	 * M�todo para adicionar os campos para valida��o
	 * @param Lumine_Base $obj Objeto que ter� um membro validado
	 * @param $campo - Nome do campo para valida��o
	 * @param $tipoValidacao - M�todo de valida��o
	 * @param $message - Mensagem a ser retornada caso encontre algo inv�lido
	 * @return boolean - Retorna verdadeiro caso valida��o inserida
	 * @author Cairo Lincoln de Morais Noleto
	 * @link http://caironoleto.wordpress.com
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 **/
	public static function addValidation(Lumine_Base $obj, $campo, $tipoValidacao, $message, $minimo = null, $maximo = null) {
			self::$validateList[ $obj->_getName() ][$campo][] = array("campo" => $campo, "tipoValidacao" => $tipoValidacao, "message" => $message, "minimo" => $minimo, "maximo" => $maximo);
	}
	
	/**
	 * Limpa a lista de valida��es de uma determinada entidade
	 *
	 * @param Lumine_Base $obj Objeto que ter� seus validators limpos
	 */
	public static function clearValidations(Lumine_Base $obj)
	{
	    self::$validateList[ $obj->_getName() ] = array();
	}

	
	/**
	 * @param Lumine_Base $obj Objeto a ser validado
	 * @return boolean - Retorna verdadeiro caso valida��o correta
	 * @return array - Retorna array contendo erros caso valida��o invalida
	 * @author Cairo Lincoln de Morais Noleto
	 * @link http://caironoleto.wordpress.com
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 **/
	public static function validate( Lumine_Base $obj ) {

		$fieldList = !empty(self::$validateList[ $obj->_getName() ]) ? self::$validateList[ $obj->_getName() ] : array();
		$errors = array();
		
		foreach ($fieldList as $fieldName => $validators)
		{
			// se j� houver um erro para o campo atual
			if( self::checkStackError($errors, $fieldName) == true )
			{
				// passa para o proximo campo
				continue;
			}
			foreach( $validators as $array )
			{
				// se j� houver um erro para o campo atual
				if( self::checkStackError($errors, $fieldName) == true )
				{
					// passa para o proximo campo
					break;
				}
				switch ($array["tipoValidacao"]) {
					//Verifica se � String
					case 'requiredString':
						if ( ! is_string($obj->$array["campo"]) || (strlen($obj->$array["campo"]) == 0) )
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
					
					//Verifica se � Numero
					case 'requiredNumber':
						if ( ! is_numeric($obj->$array["campo"]))
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
						
					//Verifica se Tamanho invalido
					case 'requiredLength':
						if( isset($array["minimo"]) )
						{
							if( strlen($obj->$array["campo"]) < $array["minimo"] )
							{
								self::stackError( $errors, $fieldName, $array['message']);
							}
						}
							
						if( isset($array["maximo"]) )
						{
							if( strlen($obj->$array["campo"]) > $array["maximo"] )
							{
								self::stackError( $errors, $fieldName, $array['message']);
							}
						}
						break;
	
					//Verifica se � email
					case 'requiredEmail':
						//Lumine_Util::validateEmail( $val );
						$res = Lumine_Util::validateEmail( $obj->$array["campo"] );
						if ($res === false)
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
					
					//Verifica se � uma data
					case 'requiredDate':
						$val = $obj->$array["campo"];
						if( ! preg_match('@^(\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}\-d{2})$@', $val) )
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
						
					//Verifica uniquidade
					// - Altera��o por Hugo: Aqui fiz uma mudan�a, porque
					//   se fosse feita um update, daria erro. por isso, checamos as chaves primarias
					case 'requiredUnique':
						$reflection = new ReflectionClass( $obj->_getName() );
	
						$objeto = $reflection->newInstance();
						$objeto->$fieldName = $obj->$fieldName;
						$objeto->find();
						
						$todas = true;
						
						while ($objeto->fetch())
						{
							$pks = $objeto->_getPrimaryKeys();
							foreach( $pks as $def )
							{
								if( $objeto->$def['name'] != $obj->$def['name'])
								{
									$todas = false;
									self::stackError( $errors, $fieldName, $array['message']);
									break;
								}
								
								if( $todas == false )
								{
									break;
								}
							}
						}
						
						unset($objeto, $reflection);
						break;
						
					//Verifica uma fun��o
					case 'requiredFunction':
						$function = new ReflectionFunction( $array['message'] );
						$result = $function->invoke( $obj->$fieldName );
						
						if ($result !== true)
						{
							//$errors[] = $result;
							self::stackError( $errors, $fieldName, $result);
						}
						
						unset($function);
						break;
						
					//Verifica se � CPF
					case 'requiredCpf':
						$res = ValidateCPF::execute($obj->$array["campo"]);
						if ($res === false)
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
						
					//Verifica se � CNPJ
					case 'requiredCnpj':
						$res = ValidateCNPJ::execute($obj->$array["campo"]);
						if ($res === false)
						{
							self::stackError( $errors, $fieldName, $array['message']);
						}
						break;
					
					default:
						return true;
					break;
				}
			}
		}

		if (!empty($errors))
		{
			return $errors;
		}
		
		return true;
	}
	
	/**
	 * M�todo auxiliar somente para colocar o nome do campo relacionado ao erro
	 * @param array $stack Pilha (array) de erros
	 * @param string $field Nome do campo
	 * @param string $value Valor a ser inserido no campo
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 */
	protected static function stackError(array &$stack, $field, $value )
	{
		if( !isset($stack[ $field ]) )
		{
			$stack[ $field ] = $value;
		}
	}
	
	/**
	 * Verifica se j� n�o existe um erro para o campo relacionado
	 * @param array $stack Pilha de erros
	 * @param string $field Nome do campo a ser checado
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br
	 * @return Boolean true se houver algum erro, false se n�o houver
	 */
	protected static function checkStackError(array &$stack, $field)
	{
		return isset($stack[ $field ]);
	}
}

?>