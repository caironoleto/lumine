<?php

class Lumine_EventListener
{

	private $_listeners     = array();
	protected $_event_types   = array();
	
	public function addEventListener($evt, $callback)
	{
		if( ! in_array($evt, $this->_event_types)) 
		{
			throw new Lumine_Exception('Tipo de evento não suportado', Lumine_Exception::ERROR);
		}
		if( ! isset($this->_listeners[ $evt ]) )
		{
			$this->_listeners[ $evt ] = array();
		}
				
		$this->_listeners[ $evt ][] = $callback;
	}
	
	public function removeEventListener($evt, $callback)
	{
		if( ! in_array($evt, $this->_event_types)) 
		{
			throw new Lumine_Exception('Tipo de evento não suportado', Lumine_Exception::ERROR);
		}
		if( ! isset($this->_listeners[ $evt ]) )
		{
			$this->_listeners[ $evt ] = array();
		}
				
		// $this->_listeners[ $evt ][] = $callback;
	}
	
	public function removeAllListeners($evt)
	{
		if( ! in_array($evt, $this->_event_types)) 
		{
			throw new Lumine_Exception('Tipo de evento não suportado', Lumine_Exception::ERROR);
		}
		$this->_listeners[ $evt ] = array();
	}
	
	protected function dispatchEvent( $evtName )
	{
		if( isset($this->_listeners[ $evtName ]) )
		{
			$args = func_get_args();
			array_shift($args);
			
			foreach($this->_listeners[ $evtName ] as $id => $callback)
			{
				call_user_func_array($callback, $args);
			}
		}
	}
	
	function __destruct()
	{
	    $this->_listeners = array();
	    $this->_event_types = array();
	}

}


?>