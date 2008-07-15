<?php
class Crianca extends Lumine_Base {
	protected $_package = 'models';
	protected $_tablename = 'criancas';
	
	protected function _initialize() {
		$this->_addField("id", "id", "int", 11, array("primary" => true, "notnull" => true, "autoincrement" => true));
		$this->_addField("nome", "nome", "varchar", 255, array("notnull" => false));
		
		$this->_addForeignRelation('brinquedos', self::MANY_TO_MANY, 'Brinquedo', 'id', 'criancas_brinquedos_relation', 'id_crianca', null);
	}
}
?>
