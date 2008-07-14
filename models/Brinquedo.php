<?php
class Brinquedo extends Lumine_Base {
	protected $_package = 'models';
	protected $_tablename = 'brinquedos';
	
	protected function _initialize() {
		$this->_addField("id", "id", "int", 11, array("primary" => true, "notnull" => true, "autoincrement" => true));
		$this->_addField("nome", "nome", "varchar", 255, array("notnull" => false));
		
		$this->_addField("crianca", "crianca", "int", 11, array("notnull" => true, 'foreign' => '1', 'onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE', 'linkOn' => 'id', 'class' => 'Crianca'));
	}

	public static function staticGet($id) {
		$obj = new Brinquedo;
		$obj->get($id);
		return $obj;
	}
}
?>
