<?php
namespace KVV\Type;

class Station {
	private $name;
	private $time;
	private $time_base;
	
	public function __construct($name, $time, $time_base) {
		$this->name = &$name;
		$this->time = &$time;
		$this->time_base = &$time_base;
	}
	
	public function getTime() {
		return Helper::getTimestamp($this->time, $this->time_base);
	}
	
	public function getName() {
		return $this->name;
	}
}
?>