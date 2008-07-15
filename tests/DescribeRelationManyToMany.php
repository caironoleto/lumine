<?php
require_once('../config/config.php');
/**
*	classname:	DescribeRelationManyToMany
*	scope:		PUBLIC
*
**/

class DescribeRelationManyToMany extends PHPSpec_Context {
	
	private $brinquedo;
	private $crianca;
	
	function before() {
		Lumine::import('Brinquedo');
		Lumine::import('Crianca');
		$this->crianca = new Crianca;
		$this->brinquedo = new Brinquedo;
	}
	
	public function itShouldCreateCriancaObject() {
		
		$this->spec($this->crianca)->should->beAnInstanceOf('Crianca');
	}
	
	public function itShouldCreateBrinquedoObject() {
		$this->spec($this->brinquedo)->should->beAnInstanceOf('Brinquedo');
	}
	
	public function itShouldBrinquedoObjectNotHaveFieldCrianca() {
		$this->spec($this->brinquedo->_getField('crianca'))->should->beFalse();
	}
	
	public function itShouldBrinquedoObjectHaveFieldCriancas() {
		$this->spec($this->brinquedo->_getField('criancas'))->should->beArray();
	}
	
	public function itShouldBrinquedoObjectFieldCriancasHaveTypeManyToMany() {
		$field = $this->brinquedo->_getField('criancas');
		$this->spec($field['type'])->should->equal(Brinquedo::MANY_TO_MANY);
	}
	
	public function itShouldCriancaObjectHaveFieldBrinquedos() {
		$this->spec($this->crianca->_getField('brinquedos'))->should->beArray();
	}
	
	public function itShouldCriancaObjectFieldBrinquedosHaveTypeManyToMany() {
		$field = $this->crianca->_getField('brinquedos');
		$this->spec($field['type'])->should->equal(Crianca::MANY_TO_MANY);
	}
}
###
?>
