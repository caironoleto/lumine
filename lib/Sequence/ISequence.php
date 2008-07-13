<?php

require_once LUMINE_INCLUDE_PATH . '/lib/Sequence/Exception.php';

interface ILumine_Sequence {
	
	public function __construct(Lumine_Base $obj);
	
	public function nextId();
	public function lastId();

	public function getSequence();
	public function dropSequence();
	public function createSequence();

	
}


?>