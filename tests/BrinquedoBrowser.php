<pre>
<?php
require_once('../config/config.php');

Lumine::import('Brinquedo');

for ($i = 1; $i <= 100; $i++) {
	$brinquedo = new Brinquedo;
	$brinquedo->nome = rand(10,99);
	$brinquedo->save();
	unset($brinquedo);
	echo '<strong>' .Lumine_Log::memoryUsage() .'</strong><br />';
}

?>
</pre>
