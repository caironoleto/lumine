<?php
require_once('../config/config.php');
/**
* This spec describe how to work Lumine Object and your specs.
**/
class DescribeHowWorkBrinquedoObject extends PHPSpec_Context {

	public $brinquedo = null;
	
	function before() {
		Lumine::import('Brinquedo');
		$this->brinquedo = new Brinquedo;
	}
	
	function itShouldBeABrinquedoInstance() {
		$this->spec($this->brinquedo)->should->beAnInstanceOf('Brinquedo');
	}
	
	function itShouldBeALumine_BaseInstance() {
		$this->spec($this->brinquedo)->should->beAnInstanceOf('Lumine_Base');
	}
}
?>
