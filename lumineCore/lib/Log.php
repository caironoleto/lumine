<?php


class Lumine_Log {
	
	const NONE                   = 0;
	const LOG                    = 1;
	const WARNING                = 2;
	const ERROR                  = 3;
	
	const BROWSER                = 'browser';
	const FILE                   = 'file';

	public static $LOG_COLOR     = '#000000';
	public static $WARNING_COLOR = '#FF9900';
	public static $ERROR_COLOR   = '#FF0000';
		
	public static $level         = 0;
	public static $output        = self::BROWSER;
	public static $filename      = '';
	
	public static function log($code, $msg, $file, $line)
	{
		$tipo = 'DESCONHECIDO';
		$cor = '';

		switch($code)
		{
			case self::LOG:
				$tipo = 'LOG';
				$cor = self::$LOG_COLOR;
				break;
				
			case self::WARNING:
				$tipo = 'ALERTA';
				$cor = self::$WARNING_COLOR;
				break;
				
			case self::ERROR:
				$tipo = 'ERRO';
				$cor = self::$ERROR_COLOR;
				break;
		}
		
		if(self::$level >= $code)
		{
			$data = date('d/m/Y H:i:s');
			$msg = "<pre style=\"color:$cor\"><strong>$data - $tipo: </strong> $msg ($file, $line)</pre>".PHP_EOL;
			switch(self::$output)
			{
				case self::BROWSER:
					echo $msg;
					break;
				
				case self::FILE:
					$msg = strip_tags($msg);
					
					if( ! empty(self::$filename))
					{
						$fp = @fopen(self::$filename, 'a+');
						if( $fp )
						{
							fwrite($fp, $msg);
							fclose($fp);
						}
					}
			}
		}
	}
	
	public static function setLevel( $newLevel = self::NONE ) 
	{
		self::$level = $newLevel;
	}
	
	public static function setOutput($type = self::BROWSER, $filename = null)
	{
		self::$output = $type;
		self::$filename = $filename;
	}
	
	public static function warning($message)
	{
		$tmp = debug_backtrace();
		$bt = array_shift( $tmp );
		$file = $bt['file'];
		$line = $bt['line'];
		self::log(self::WARNING, $message, $file, $line);
	}
	
	public static function debug($message)
	{
		$tmp = debug_backtrace();
		$bt = array_shift( $tmp );
		$file = $bt['file'];
		$line = $bt['line'];
		self::log(self::LOG, $message, $file, $line);
	}
	
	public static function error($message)
	{
		$bt = array_shift(debug_backtrace());
		$file = $bt['file'];
		$line = $bt['line'];
		self::log(self::ERROR, $message, $file, $line);
	}
	
	public static function memoryUsage($real_usage = true) {
		return memory_get_usage()/1048576 . ' MB';
	}
}


?>
