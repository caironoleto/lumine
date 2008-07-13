<?php

class Lumine_Tokenizer 
{
	
	public static function dataSelect( $dataStr, $obj )
	{
		$idx = 0;
		$total = strlen($dataStr);
		$d =',';
		$tokens = array();

		$inStr = false;
		$inFunction = 0;
		$inStrStart = '';
		
		for($i=0; $i<$total; $i++)
		{
			$c = substr($dataStr, $i, 1);
			
			if($c == '(' && ! $inStr)
			{
				$inFunction++;
			}
			if($c == ')' && ! $inStr)
			{
				$inFunction--;
			}
			if( ! $inStr && ($c == '"' || $c == "'") && substr($dataStr, $i-1, 1) != '\\' && $c != '\\')
			{
				$inStr = true;
				$inStrStart = $c;
			}
			
			/*
			if( $inStr && $c == $inStrStart && substr($dataStr, $i-1, 1) != '\\' && $c != '\\')
			{
				$inStr = false;
				$inStrStart = '';
			}
			*/
			
			if($inStr == true && $c == $inStrStart)
			{
				$tmp_test = str_replace( $obj->_getConnection()->getEscapeChar() . $inStrStart, '', $c . $tokens[$idx] . $c);
				if( substr_count($tmp_test, "'") % 2 == 0 )
				{
					$inStr = false;
					$tmp = '';
					$inStrStart = '';
				}
			} 

			if( $inFunction == 0 && ! $inStr && $c == $d)
			{
				$idx++;
				continue;
			}
			
			if(!isset($tokens[$idx])) 
			{
				$tokens[$idx] = '';
			}
			$tokens[$idx] .= $c;
		}
		
		foreach($tokens as $id => $token)
		{
			$tokens[ $id ] = trim($token);
		}
		
		return $tokens;
	}
	
	
	public function where( $str )
	{
		
	}
	
}




?>