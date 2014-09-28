<?php
namespace KVV\Type;

class Stop {
	private $id;
	private $name;
	private $lat;
	private $lon;
	
	public function __construct($id, $name, $lat, $lon) {
		$this->id = $id;
		$this->name = $name;
		$this->lat = $lat;
		$this->lon = $lon;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getLat() {
		return $this->lat;
	}
	
	public function getLon() {
		return $this->lon;
	}
}
?>