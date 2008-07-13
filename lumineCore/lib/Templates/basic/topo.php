<html>
<head>
<title>Entidade controle para <?php echo $this->obj->_getName(); ?></title>
</head>
<body>
<h1>Entidade controle para <?php echo $this->obj->_getName(); ?></h1>

<p>Editar outra entidade</p>
<select onchange="location.href = this.value">
<option value="">-- Selecione </option>
<?php
$files = glob('*.php');

foreach($files as $file)
{
	echo '<option value="'.$file.'">' . array_shift(explode('.', $file)).'</option>'.PHP_EOL;
}

?>
</select>