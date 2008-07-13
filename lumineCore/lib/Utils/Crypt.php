<?php


class Lumine_Crypt
{
	public static function _get_rnd_iv($iv_len, $pass_len) {
		$iv = '';
		while ($iv_len-- > 0) {
			$iv .= chr($pass_len & 0xff);
		}
		return $iv;
	}
	
	public static function encrypt($plain_text, $obj = false, $password = false, $iv_len = 16) {
		if($password === false) {
			if(isset($obj->oTable->config->config['crypt-pass'])) {
				$password = $obj->oTable->config->config['crypt-pass'];
			}
		}
		if($password === false) {
			return $plain_text;
		}
		
		$plain_text .= "\x13";
		$n = strlen($plain_text);
		if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
		$i = 0;
		$enc_text = self::_get_rnd_iv($iv_len, strlen($password));
		$iv = substr($password ^ $enc_text, 0, 512);
		while ($i < $n) {
			$block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
			$enc_text .= $block;
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return base64_encode($enc_text);
	}
	
	public static function decrypt($enc_text, $obj = false, $password = false, $iv_len = 16) {
		if($password === false) {
			if(isset($obj->oTable->config->config['crypt-pass'])) {
				$password = $obj->oTable->config->config['crypt-pass'];
			}
		}
		if($password === false) {
			return $enc_text;
		}
		$enc_text = base64_decode($enc_text);
		$n = strlen($enc_text);
		$i = $iv_len;
		$plain_text = '';
		$iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
		while ($i < $n) {
			$block = substr($enc_text, $i, 16);
			$plain_text .= $block ^ pack('H*', md5($iv));
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return preg_replace('/\\x13\\x00*$/', '', $plain_text);
	}
}


?>