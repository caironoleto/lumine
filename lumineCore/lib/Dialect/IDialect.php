<?php

interface ILumine_Dialect
{

	function __construct($fetchMode);
	public function getConnection();
	public function setFetchMode($mode);
	public function getFetchMode();
	public function setTablename($tablename);
	public function getTablename();
	
	public function execute($sql);
	public function num_rows();
	public function affected_rows();
	public function moveNext();
	public function movePrev();
	public function moveFirst();
	public function moveLast();
	public function fetch_row($rowNumber);
	public function fetch();
	public function getErrorMsg();
	
	public function getDataset();
	public function setDataset(array $dataset);
	
	public function getPointer();
	public function setPointer($pointer);
	
	public function getLumineType($nativeType);
}

?>
