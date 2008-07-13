<?php

Lumine::load('Validator_XMLValidator');
Lumine::load('Validator_ClassValidator');
Lumine::load('Validator_PHPValidator');

/**
 * Classe abstrata para validaчуo
 */

abstract class Lumine_Validator
{

	/**
	 * Classe de validaчѕes registradas
	 */
	protected static $registered_validators = array (
		'Lumine_Validator_XMLValidator',
		'Lumine_Validator_ClassValidator',
		'Lumine_Validator_PHPValidator'
	);

	/**
	 * Registra um novo validator
	 * @param string $name Nome da classe a ser registrada 
	 */
	public static function registerValidator( $name )
	{
		array_push( self::$registered_validators, $name );
		self::$registered_validators = array_unique(self::$registered_validators);
	}

	/**
	 * Efetua a validaчуo
	 * @param Lumine_Base $obj Objeto a ser validado
	 * @author Hugo Ferreira da Silva
	 */
	public static function validate(Lumine_Base $obj)
	{
		############################################################################
		## Aqui vamos checar todos os tipos padrуo de validaчуo
		## e armazenar os resultados em um array
		## para que o objeto passe na validaчуo, todos os retornos devem ser TRUE
		## para isto, utilizaremos a interface de reflexуo
		############################################################################
		// aqui armazenamos o resultado das validaчѕes
		$results = array();

		// primeiro, carrega as classes registradas
		foreach(self::$registered_validators as $classname)
		{
			$ref = new ReflectionMethod( $classname, 'validate' );
			$results[] = $ref->invoke( null, $obj );
		}
		
		############################################################################
		## vamos checar se todos retornar true ou se algum deu erro
		############################################################################
		
		$tudo_ok = true;
		$erros = array();
		
		foreach( $results as $item )
		{
			if( $item !== true )
			{
				$tudo_ok = false;
				if( is_array($item) )
				{
					$erros = array_merge( $erros, $item );
				}
			}
		}
		
		// se deu erro em algum
		if( $tudo_ok == false )
		{
			// colocaremos os erros no array $_REQUEST
			
			foreach( $results as $item )
			{
				if( is_array($item) )
				{
					foreach( $item as $chave => $erro )
					{
						if( $erro !== true )
						{
							$_REQUEST[ $chave . '_error' ] = $erro;
						}
					}
				}
			}
			
			// retorna os erros
			return $erros;
		}
		
		// certo, passou em tudo, retorna verdadeiro
		return true;
	}

}


?>