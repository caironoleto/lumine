<?php
class Brinquedo extends Lumine_Base {
	protected $_package = 'models';
	protected $_tablename = 'brinquedos';
	
	protected function _initialize() {
		$this->_addField("id", "id", "int", 11, array("primary" => true, "notnull" => true, "autoincrement" => true));
		$this->_addField("nome", "nome", "varchar", 255, array("notnull" => false));
		
		$this->_addForeignRelation('criancas', self::MANY_TO_MANY, 'Crianca', 'id', 'criancas_brinquedos_relation', 'id_brinquedo', null);
	}

	public static function staticGet($id) {
		$obj = new Brinquedo;
		$obj->get($id);
		return $obj;
	}
}
?>
