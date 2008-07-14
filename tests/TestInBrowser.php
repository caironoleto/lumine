<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<pre>
<?php
require_once('../config/config.php');

Lumine::import('Brinquedo', 'Crianca');

#for ($i = 1; $i <= 100; $i++) {
#	$crianca = new Crianca;
#	for ($j = 1; $j <= 10; $j++) {
#		$brinquedos[] = Brinquedo::staticGet(rand(7653, 8652));
#	}
#	echo '<strong>Brinquedos: ' .Lumine_Log::memoryUsage() .'</strong><br />';
#	$crianca->nome = rand(10,99);
#	$crianca->brinquedos = $brinquedos;
#	$crianca->save();
#	unset($crianca);
#	unset($brinquedos);
#	echo '<strong>Criança: ' .Lumine_Log::memoryUsage() .'</strong><br />';
#}

$crianca = new Crianca;
$crianca->find();
while($crianca->fetch()) {
	echo '<strong>Memory Usage before getLink: ' .Lumine_Log::memoryUsage() .'</strong><br />';
	$brinquedos = $crianca->_getLink('brinquedos');
	unset($brinquedos);
	echo 'Criança ID: ' .$crianca->id .'<br />';
	echo '<strong>Memory Usage after getLink: ' .Lumine_Log::memoryUsage() .'</strong><br />';
}
$crianca->destroy();
unset($crianca);
echo '<strong>Memory Usage after destroy Crianca obj: ' .Lumine_Log::memoryUsage() .'</strong><br />';
?>
</pre>
