<?php
/**
 * Define o diretório absoluto de onde Lumine se encontra
 */
define('LUMINE_INCLUDE_PATH', dirname(__FILE__));

/**
 * Classe principal
 *
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br/lumine
 */
abstract class Lumine
{
	/**
	 * Carrega arquivos do pacote
	 *
	 * @author Hugo Ferreira da Silva
	 * @return void
	 */
	public static function load()
	{
		$args = func_get_args();
		foreach($args as $libname)
		{
			$basedir = LUMINE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
			$libname = preg_replace('@^Lumine_@', '', $libname);
			$newfile = $basedir . str_replace('_', DIRECTORY_SEPARATOR, $libname). '.php';
		
			require_once $newfile;
		}
	}
	
	/**
	 * Importa arquivos
	 *
	 * @author Hugo Ferreira da Silva
	 * @author Cairo Noleto
	 * @return boolean
	 */
	public static function import()
	{
		$args = func_get_args();
		$cn = Lumine_ConnectionManager::getInstance();
		$list = $cn->getConfigurationList();

		foreach ($list as $cfg) {
			$pacotes[] = $cfg->getProperty('package');
		}
		
		echo $libname;
		
		foreach($args as $classname) {
			$newfile = $basedir . str_replace('.', DIRECTORY_SEPARATOR, $libname). '.php';
			foreach ($pacotes as $pacote) {
				Lumine_Util::Import( $pacote .'.' . $classname );
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Checa se um valor é realmente nulo
	 *
	 * @param mixed $val Valor a ser comparado
	 * @author Hugo Ferreira da Silva
	 * @return boolean True se for nulo, do contrário false
	 */
	public static function is_empty($val)
	{
		return gettype($val) == 'NULL';
	}
}

// carrega principais dependências
Lumine::load('Exception','EventListener','Tokenizer','Parser','Exception','Configuration','ConnectionManager','Base','Validator','Union');
Lumine::load('Utils_Util','Utils_Crypt');

?>
