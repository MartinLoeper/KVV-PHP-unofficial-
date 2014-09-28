<?php
namespace KVV\Type;

class StopExt extends Stop {
	private $distance;
	
	public function __construct($id, $name, $lat, $lon, $distance) {
		$this->distance = &$distance;
		parent::__construct($id, $name, $lat, $lon);
	}
	
	public function getDistance() {
		return $this->distance;
	}
}
?>