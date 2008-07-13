<?php

interface ILumine_Form
{

	public function getTop();
	public function createForm($action = null);
	public function getInputFor($nome);
	public function getCalendarFor($name);
	public function getFooter();
	public function showList($offset, $limit, $fieldSort = null, $order = null);
	public function handleAction($actionName, array $values);

	public function getControlTemplate($cfg, $className);
}



?>