<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<pre>
<?php
ini_set('max_execution_time', 300);
require_once('../config/config.php');

Lumine::import('Brinquedo', 'Crianca');

//Lumine_Log::setLevel(Lumine_Log::ERROR);
//Lumine_Log::setOutput();

//for ($i = 1; $i <= 10000; $i++) {
//	echo '<strong>START</strong><br/>';
//	echo '<strong>Memory Usage before create Crianca obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//	$crianca = new Crianca;
//	for ($j = 1; $j <= 10; $j++) {
//		$brinquedos[] = Brinquedo::staticGet(rand(7653, 8652));
//	}
//	echo '<strong>Brinquedos: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//	$crianca->nome = rand(10,99);
//	$crianca->brinquedos = $brinquedos;
//	$crianca->save();
//	$crianca->destroy();
//	echo '<strong>Memory Usage before destroy Brinquedos obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//	foreach($brinquedos as $value) {
//		$value->destroy();
//		unset($value);
//	}
//	echo '<strong>Memory Usage after destroy Brinquedos obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//	unset($crianca, $brinquedos);
//	echo '<strong>After save and destroy Crianca obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//	echo '<strong>END</strong><br/><br/>';
//}
//echo '<strong>Memory Usage after foreach: ' .Lumine_Log::memoryUsage() .'</strong><br />';
//echo '<strong>Memory Usage before create Crianca obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
$crianca = new Crianca;
echo 'Criança ' .$crianca->getObjId() .'<br />';
$crianca->find();
while($crianca->fetch()) {
//	echo '<strong>Memory Usage before getLink: ' .Lumine_Log::memoryUsage() .'</strong><br />';
	$crianca->_getLink('brinquedos');
	print_r($crianca->brinquedos);
//	foreach ($brinquedos as $brinquedo) {
//		echo '		Brinquedo ' .$brinquedo->getObjId() .'<br />';
//	}
	unset($brinquedos);
//	echo 'Criança ID: ' .$crianca->id .'<br />';
//	echo '<strong>Memory Usage after getLink: ' .Lumine_Log::memoryUsage() .'</strong><br />';
}
$crianca->destroy();
unset($crianca);
echo '<strong>Memory Usage after destroy Crianca obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
?>
</pre>
