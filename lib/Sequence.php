<?php

Lumine::load('Sequence_Exception');

class Lumine_Sequence extends Lumine_EventListener
{

	const NATURAL                 = 1;
	const SEQUENCE                = 2;
	const COUNT_TABLE             = 3;

	private $obj                  = null;
	private $seq_obj              = null;
	
	public function __construct(Lumine_Base $obj)
	{
		$this->obj = $obj;
	}
	
	public function getSequence( $field )
	{
		$st = null;
		$con_st = $this->obj->_getConnection()->getOption('sequence_type');
		
		if( empty($field['sequence_type']))
		{
			$st = $con_st;
		} else {
			$st = $field['sequence_type'];
		}
		
		$dialect = $this->obj->_getConfiguration()->getProperty('dialect');
		
		switch($st)
		{
			case self::SEQUENCE:
				$class = $dialect."_Sequence";
				Lumine::load('Sequence_'.$class);
				$this->seq_obj = new $class( $obj, $field );
			break;
			
			case self::COUNT_TABLE:
				$class = $dialect."_Count";
				Lumine::load('Sequence_'.$class);
				$this->seq_obj = new $class( $obj, $field );
			break;
			
			case self::NATURAL:
			default:
				$class = $dialect."_Natural";
				Lumine::load('Sequence_'.$class);
				$this->seq_obj = new $class( $obj, $field );
		}
		
		$this->seq_obj->createSequence();
		return $this->seq_obj;
	}
	
	
}

?>