<?php

class ValidateCPF
{
	public function execute( $cpf )
	{
		/******************************
		Calculando o primeiro digito
		******************************/
		$cpf = preg_replace('@\D@','', $cpf);				// remove caracteres que não são números

		if(strlen($cpf) != 11)								// se for diferente de 11 caracteres
		{
			return false;									// inválido
		}
		
		if(preg_match('@(\d{1})\1{10}@', $cpf))				// se a pessoa digitiou 11 numeros iguais
		{
			return false;									// inválido
		}
		
		$dv_informado  = substr($cpf, 9,2);					// pega o digido verificador informado

		$soma          = 0;									// soma para calculo do primeiro digito
		$multiplicador = 10;
		
		for($i=0; $i<9; $i++)								// para cada um dos 9 primeiros digitos
		{
			$numeroatual = substr($cpf, $i, 1);				// pega o desejado
			$soma       += ($numeroatual * $multiplicador); // efetua o calculo para soma do digito atual e soma com o resultado anterior
			$multiplicador --;								// diminui o valor do multiplicador
		}
		
		$dv1_encontrado = $soma % 11;						// pega o resto da divisão por 11 para saber o primeiro DV
		if($dv1_encontrado < 2)								// se o DV encontrado for 1 ou 0 (ou seja, menor que 2)
		{
			$dv1_encontrado = 0;							// coloca o valor como 0
		} else {											// mas se for maior ou igual a 2
			$dv1_encontrado = 11 - $dv1_encontrado;			// substrai o valor encontrado de 11
		}

		/******************************
		Calculando o segundo digito
		******************************/
		$multiplicador = 11;
		$soma = 0;
		
		for($i=0; $i<10; $i++)								// agora pegamos os 10 primeiros valores do CPF informado
		{
			$numeroatual = substr($cpf, $i, 1);				// pega o desejado
			$soma       += ($numeroatual * $multiplicador); // efetua o calculo para soma do digito atual e soma com o resultado anterior
			$multiplicador --;								// diminui o valor do multiplicador
		}


		$dv2_encontrado = $soma % 11;						// pega o resto da divisão por 11 para saber o primeiro DV
		if($dv2_encontrado < 2)								// se o DV encontrado for 1 ou 0 (ou seja, menor que 2)
		{
			$dv2_encontrado = 0;							// coloca o valor como 0
		} else {											// mas se for maior ou igual a 2
			$dv2_encontrado = 11 - $dv2_encontrado;			// substrai o valor encontrado de 11
		}
		
		$dv_final = $dv1_encontrado. $dv2_encontrado;		// monta o DV encontrado no calcula
		
		if($dv_final != $dv_informado)						// se o DV informado é diferente do encontrado no calculo
		{
			return false;									// CPF inválido
		}
		
		// se chegou até aqui, é porque é valido, EBA!
		return true;
	}
}


?>