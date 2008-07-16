<?php
require_once('../config/config.php');
/**
*	classname:	DescribeHowToWorkLumineBaseObject
*	scope:		PUBLIC
*
**/
class DescribeHowToWorkLumineBaseObject extends PHPSpec_Context {
	
	private $lumineObj;
	private $lumineObj2;
	
	function before() {
		Lumine::import('Crianca');
		$this->lumineObj = new Crianca;
	}
	
	function itShouldPrintLumineStrucuture() {
//		print_r($this->lumineObj);
		$this->lumineObj->where('id <= 42000');
		$this->lumineObj->find(true);
//		$this->lumineObj->brinquedos = array($this->lumineObj2);
		$this->lumineObj->brinquedos = 5191919819;
//		$this->lumineObj->toArray();
		print_r($this->lumineObj->allToArray());
	}
}
?>
