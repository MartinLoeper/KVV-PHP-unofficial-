<?php
namespace KVV\Type;

/**
	A representation of a location in the kvv system.
**/
class Location {
	private $name;
	private $type;
	
	const TYPE_STOP = 'stop';	// Haltestelle
	const TYPE_LOCATION = 'loc';	// Adresse
	const TYPE_POINT = 'poi';		// Wichtiger Punkt
	
	public function __construct($name, $type) {
		$this->name = &$name;
		$this->type = &$type;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getNameWithType() {
		switch($this->getType()) {
			case self::TYPE_STOP:
				return $this->getName."[Haltestelle]";
			break;
			case self::TYPE_POINT:
				return $this->getName."[Wichtiger Punkt]";
			break;
			case self::TYPE_LOCATION:
				return $this->getName."[Adresse]";
			break;
		}
	}
}
?>