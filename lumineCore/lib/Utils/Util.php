<?php

class Lumine_Util
{
	private static $time_start;
	private static $time_end;
	private static $consultas  = 0;
	
	public static function import()
	{
		$list = func_get_args();
		foreach($list as $item)
		{
			$parts = explode(".", $item);
			$class = array_pop($parts);
			
			$cm = Lumine_ConnectionManager::getInstance();
			$cfg = $cm->getConfiguration( implode('.', $parts) );
			if($cfg != false)
			{
				$cfg->import($class);
			}
		}
	}
	
	public function counter()
	{
		$this->consultas++;
	}
	
	public function iniciar()
	{
		$this->time_start = microtime();
	}
	
	public function results()
	{
		$this->time_end = microtime();
		
		if( empty($this->time_start) )
		{
			return;
		}
		
		list($m_start, $s_start) = explode(' ', $this->time_start);
		list($m_end, $s_end) = explode(' ', $this->time_end);
		
		$secs = $s_end - $s_start;
		if($m_start > $m_end) {
			$mil = $m_start - $m_end;
			$secs--;
		} else {
			$mil = $m_end - $m_start;
		}
		
		$result = array(
			'queries' => $this->consultas,
			'tempo'   =>  number_format($secs + $mil, 5, '.', '')
		);
		
		return $result;
	}
	
	public static function FormatDate($date, $format = "%d/%m/%Y") {
		$v = $date;
		if(is_numeric($date)) {
			return strftime($format, $date);
		}
		$formats = array("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/",
						"/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/");
		//$replaces = array(
		if(preg_match($formats[0], $date, $d)) {
			$v = $date;
		}
		if(preg_match($formats[1], $date, $d)) {
			if(checkdate($d[2], $d[1], $d[3])) {
				$v = "$d[3]-$d[2]-$d[1]";
			} else {
				$v = "$d[3]-$d[1]-$d[2]";
			}
		}
		$s = strtotime($v);
		if($s > -1) {
			return strftime($format, $s);
		}
		return $v;
	}

	public static function FormatTime($time, $format = "%H:%M:%S") {
		if(is_numeric($time)) {
			return strftime($time, $format);
		}
		$v = $time;
		$t = strtotime($v);
		if($t > -1) {
			$v = strftime($format, $t);
		}
		return $v;
	}
	
	public static function FormatDateTime($time, $format = "%Y-%m-%d %H:%M:%S") {
		if(is_numeric($time)) {
			return strftime($format, $time);
		}
		// 2005-10-15 12:29:32
		if(preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $time, $reg)) {
			return strftime($format, strtotime($time));
		}
		// 2005-10-15 12:29
		if(preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/", $time, $reg)) {
			return strftime($format, strtotime($time));
		}
		// 2005-10-15 12
		if(preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})$/", $time, $reg)) {
			return strftime($format, strtotime($time));
		}
		// 2005-10-15
		if(preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $time, $reg)) {
			return self::FormatDate($time, $format);
		}
		// 15/10/2005 12:29:32
		if(preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $time, $reg)) {
			$isodate = self::FormatDate("$reg[1]/$reg[2]/$reg[3]", "%Y-%m-%d");
			return strftime($format, strtotime("$isodate $reg[4]:$reg[5]:$reg[6]"));
		}
		// 15/10/2005 12:29
		if(preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2})$/", $time, $reg)) {
			$isodate = self::FormatDate("$reg[1]/$reg[2]/$reg[3]", "%Y-%m-%d");
			return strftime($format, strtotime("$isodate $reg[4]:$reg[5]:00"));
		}
		// 15/10/2005 12
		if(preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2})$/", $time, $reg)) {
			$isodate = self::FormatDate("$reg[1]/$reg[2]/$reg[3]", "%Y-%m-%d");
			return strftime($format, strtotime("$isodate $reg[4]"));

		}
		// 15/10/2005
		if(preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/", $time, $reg)) {
			return self::FormatDate($time, $format);
		}
		return $time;
	}
	
	public static function mkdir($dir, $dono = false) {
		if(file_exists($dir) && is_dir($dir)) {
			return true;
		}
		$dir = str_replace("\\","/", $dir);
		$pieces = explode("/", $dir);
		
		for($i=0; $i<count($pieces); $i++) {
			$mdir = '';
			for($j=0; $j<=$i; $j++) {
				$mdir .= $pieces[$j] != '' ? $pieces[$j] . "/" : '';
			}
			$mdir = substr($mdir, 0, strlen($mdir)-1);
			if(!file_exists($mdir) && $mdir != '') {
				mkdir($mdir, 0777) or die("Falha ao criar o diretório <strong>$mdir</strong>");
				@chmod($mdir, 0777);
				if($dono !== false) {
					chown($mdir, $dono);
				}
			}
		}
		return true;
	}
	
	public static function validateEmail ($email ) {
		if($email == '') {
			return false;
		}
		return (boolean)ereg("^([0-9,a-z,A-Z]+)([.,_,-]*([0-9,a-z,A-Z]*))*[@]([0-9,a-z,A-Z]+)([.,_,-]([0-9,a-z,A-Z]+))*[.]([0-9,a-z,A-Z]){2}([0-9,a-z,A-Z])?$", $email);
	}

	public static function buildOptions($class, $value, $label, $selected='', $where=null) {
		if(is_string($class)) {
			self::Import($class);
			
			$classname = array_pop(explode('.', $class));
			
			$o = new $classname;
			
			if($o) {
				if( !empty($where)) {
					$o->where($where);
				}
				$o->select("$value, $label");
				$o->order("$label asc");
				$o->find();
			}
		} else if(is_a($class, 'Lumine_Base')) {
			$o = &$class;
		} else {
			return false;
		}
		
		$str='';
		while($o->fetch()) {
			$str .= '<option value="'.$o->$value.'"';
			if($o->$value == $selected) {
				$str .= ' selected="selected"';
			}
			$str .= '>'.$o->$label.'</option>' . PHP_EOL;
		}
		return $str;
	}
	
	public static function toUTF8( $o ) {
		if(is_string($o)) {
			//$o = preg_replace('/([^\x09\x0A\x0D\x20-\x7F]|[\x21-\x2F]|[\x3A-\x40]|[\x5B-\x60])/e', '"&#".ord("$0").";"', $o);
			$o = utf8_encode($o);
			//$o = preg_replace('@&([a-z,A-Z,0-9]+);@e','html_entity_decode("&\\1;")',$o);
			return $o;
		}
		if(is_array($o)) {
			foreach($o as $k=>$v) {
				$o[$k] = self::toUTF8($o[$k]);
			}
			return $o;
		}
		if(is_object($o)) {
			$l = get_object_vars($o);
			foreach($l as $k=>$v) {
				$o->$k = self::toUTF8( $v );
			}
		}
		// padrão
		return $o;
	}

	public static function fromUTF8( $o ) {
		if(is_string($o)) {
			//$o = preg_replace('/([^\x09\x0A\x0D\x20-\x7F]|[\x21-\x2F]|[\x3A-\x40]|[\x5B-\x60])/e', '"&#".ord("$0").";"', $o);
			$o = utf8_decode($o);
			//$o = preg_replace('@&([a-z,A-Z,0-9]+);@e','html_entity_decode("&\\1;")',$o);
			return $o;
		}
		if(is_array($o)) {
			foreach($o as $k=>$v) {
				$o[$k] = self::fromUTF8($o[$k]);
			}
			return $o;
		}
		if(is_object($o)) {
			$l = get_object_vars($o);
			foreach($l as $k=>$v) {
				$o->$k = self::fromUTF8( $v );
			}
		}
		// padrão
		return $o;
	}
	
	public static function showResult(Lumine_Base $obj)
	{
		$resultset = $obj->allToArray();
		
		if( !empty($resultset) )
		{
			$header = $resultset[0];
			
			$style = ' style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:9px" ';
			
			echo '<table cellpadding="2" cellspacing="1" width="100%">';
			echo '<tr>';
			
			echo '<tr>'.PHP_EOL;
			echo '<td '.$style.' colspan="'.count($header).'">' . $obj->_getSQL(). '</td>'.PHP_EOL;
			echo '</tr>' . PHP_EOL;
			
			foreach($header as $key => $value)
			{
				echo '<td'.$style.' bgcolor="#CCCCCC">'. $key .'</td>'.PHP_EOL;
			}
			echo '</tr>';
			
			for($i=0; $i<count($resultset); $i++)
			{
				$row = $resultset[$i];
				$cor = $i%2!=0?'#EFEFEF':'#FFFFFF';
				echo '<tr>';
				foreach($row as $value)
				{
					echo '<td'.$style.' bgcolor="'.$cor.'">'.$value.'</td>'.PHP_EOL;
				}
				echo '</tr>';
			}
			
			echo '</table>';
		} else {
			Lumine_Log::warning( 'Nenhum resultado encontrado no objeto passado: ' . get_class($obj) );
		}
	}
}



?>