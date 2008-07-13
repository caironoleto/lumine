<?php


interface Lumine_IReport {
	public function run();
	public function parseFromModel( $tpl );
	public function output();
	
}


?>