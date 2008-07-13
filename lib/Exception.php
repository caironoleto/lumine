<?php

class Lumine_Exception extends Exception
{

	const LOG                   = 0;
	const ERROR                 = 1;
	const WARNING               = 2;

	const CONFIG_NO_DIALECT     = 10;
	const CONFIG_NO_DATABASE    = 11;
	const CONFIG_NO_USER        = 12;
	const CONFIG_NO_CLASSPATH   = 13;
	const CONFIG_NO_PACKAGE     = 14;
	
	const QUERY_ERROR           = 20;
	
	function __construct($msg, $code)
	{
		$bt = array_shift(debug_backtrace());
		$file = $bt['file'];
		$line = $bt['line'];
		Lumine_log::log($code, $msg, $file, $line);
		parent::__construct($msg, $code);
	}
}


?>