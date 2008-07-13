<?php
require '../lumineCore/Lumine.php';
//Lumine_Log::setLevel(Lumine_Log::ERROR);
//Lumine_Log::setOutput();

$lumineConfig = array(
	'dialect' => 'MySQL',
	'database' => 'lumine_test',
	'user' => 'lumine_test',
	'password' => 'lumine_test',
	'port' => '3306',
	'host' => 'localhost',
	'class_path' => '/home/cairo/lumine-framework/',
	'package' => 'models',
	'options' => array(
		//<- BEGIN -> Reverse Engineering
		'schema_name' => 'lumine_test',
		'generate_files' => '1',
		'generate_zip' => '',
		'class_sufix' => '',
		'remove_count_chars_start' => '',
		'remove_count_chars_end' => '',
		'remove_prefix' => '',
		'create_entities_for_many_to_many' => '',
		'plural' => 's',
		'many_to_many_style' => '',
		'create_controls' => '',
		//<- END -> Reverse Engineering
		'xml_validation_path' => '/home/cairo/lumine-framework/xml_validators',
		'php_validator_path' => '/home/cairo/lumine-framework/php_validators'
	)
);

$cfg = new Lumine_Configuration($lumineConfig);

?>
