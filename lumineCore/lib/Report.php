<?php

/**
 * Classe para geração de relatórios
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br
 *
 */

//Lumine::load('Validator_XMLValidator');

class Lumine_Report extends Lumine_EventListener
{

	/**
	 * tipos de eventos disparados
	 */
	protected $_event_types  = array(
		'onPreCreate','onCreateFinish'
	);
	
	/**
	 * Formatos de arquivos disponíveis
	 */
	protected $_formats = array('HTML','PDF');
	protected $columns = array();
	protected $format;
	protected $obj;

	/**
	 * Construtor da classe
	 * @param Lumine_Base $obj Objeto a ser gerado o relatório
	 * @param string $format Formato final do arquivo
	 */
	function __construct( Lumine_Base $obj, $format = null )
	{
		$this->obj = $obj;
		
		if( !is_null($format) )
		{
			$this->setFormat( $format );
		}
	}
	
	function addColumn( $prop )
	{
		if( !isset($prop['name']) || !isset($prop['header']) || !isset($prop['width']) )
		{
			throw new Exception('Formato de coluna inválida. Você deve informar as propriedades "name","header" e "width"');
		}
		
		$this->columns[] = $prop;
	}
	
	function removeColumn( $prop )
	{
		$nova = array();
		foreach( $this->columns as $column )
		{
			if( $column != $prop )
			{
				$nova[] = $column;
			}
		}
		$this->columns = $nova;
	}
	
	function setColumns( $arrayColumns )
	{
		$old = $this->columns;
		try {
			$this->columns = array();
			foreach( $arrayColumns as $column )
			{
				$this->addColumn( $column );
			}
		} catch(Exception $e ) {
			Lumine_Log::warning('Formato de coluna inválido, restaurando anterior...');
			$this->columns = $old;
		}
	}

	/*
	 * formato do relatorio
	 */
	public function setFormat( $fm )
	{
		if( !in_array($fm, $this->_formats) )
		{
			throw new Exception('Formato não suportado: '.$fm);
		}
		$this->format = $fm;
	}
	public function getFormat()
	{
		return $this->format;
	}

	
	/**
	 * Cria o relatorio
	 */
	public function create()
	{
		$this->dispatchEvent('onPreCreate', $this);
		$this->result = $this->run();
		$this->dispatchEvent('onCreateFinish', $this);
	}
	
	public static function PDF( Lumine_Base $obj )
	{
		Lumine::load('Report_PDF');
		$obj = new Lumine_Report_PDF( $obj, 'PDF' );
		return $obj;
	}

}
?>