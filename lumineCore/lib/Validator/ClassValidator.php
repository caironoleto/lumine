<?php

/**
 * Esta classe irá procurar todos os métodos que inicia com validate no objeto principal
 */

class Lumine_Validator_ClassValidator
{

	/**
	 * Efetua a validação
	 */
	public static function validate(Lumine_Base $obj)
	{
		$erros = array();
		
		$metodos = get_class_methods( $obj );
		
		foreach( $metodos as $metodo ) 
		{
			$ref = new ReflectionMethod( $obj, $metodo );
			if( preg_match('@^validate(\w+)@', $metodo) && $ref->isPublic() == true )
			{
				$result = $ref->invoke( $obj );
				
				if( $result !== true )
				{
					$erros[] = $result;
				}
			}
			
			unset($ref, $result);
		}
		
		if( !empty($erros) )
		{
			return $erros;
		}
		
		return true;
	}

}


?>