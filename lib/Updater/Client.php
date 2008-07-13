<?php

class Lumine_Updater_Client extends Lumine_Updater
{

	protected $_event_types = array(
		'preUpdate','posUpdate','updateError'
	); 
	
	private $serverUrl = '';
	
	public function getServerUrl()
	{
		return $this->serverUrl;
	}
	
	public function setServerUrl( $url )
	{
		$this->serverUrl = $url;
	}
	
	
	public function getUpdateList()
	{
		preg_match('@^http://(.+?)/(.+?)$@', $this->getServerUrl(), $parts);
		
		$fp = fsockopen($parts[1], 80, $errno, $erstr);
		if($fp == false);
	}
}


?>