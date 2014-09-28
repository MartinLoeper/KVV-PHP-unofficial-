<?php
namespace KVV\Type;

class Section {
	private $infos;
	private $origin_time;
	private $origin_place;
	private $destination_time;
	private $destination_place;
	private $stations;
	private $time_base;
	
	public function __construct($infos, $origin_time, $origin_place, $destination_time, $destination_place, $stations, $time_base) {
		$this->infos = &$infos;
		$this->origin_place = trim($origin_place);
		$this->origin_time = trim($origin_time);
		$this->destination_place = trim($destination_place);
		$this->destination_time = trim($destination_time);
		$this->stations = &$stations;
		$this->time_base = &$time_base;
	}
	
	public function getInfos() {
		return $this->infos;
	}
	
	public function getOriginTime() {
		return strtotime($this->origin_time, $time_base);	// what happens if time is before timestamp???
	}
	
	public function getOriginPlace() {
		return $this->origin_place;
	}
	
	public function getDestinationTime() {
		return strtotime($this->destination_time, $time_base);
	}
	
	public function getDestinationPlace() {
		return $this->destination_place;
	}
	
	public function getStations() {
		return $this->stations;
	}
}
?>