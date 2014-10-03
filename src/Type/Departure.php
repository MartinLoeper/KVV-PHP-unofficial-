<?php
namespace KVV\Type;

class Departure {
	private $route;
	private $destination;
	private $direction;
	private $lowfloor;
	private $realtime;
	private $stopPosition;
	private $time;
	private $timestamp;
	private $traction;
	
	public function __construct($route, $destination, $direction, $lowfloor, $realtime, $stopPosition, $time, $traction) {
		$this->route = $route;
		$this->destination = $destination;
		$this->direction = (int)$direction;
		$this->lowfloor = $lowfloor;
		$this->realtime = $realtime;
		$this->stopPosition = (int)$stopPosition;
		$this->time = $time;
		if(strtolower(substr($time, -1)) == 'h')
			$this->timestamp = strtotime('+'.substr($time, 0, -1).' hours');
		else if(strtolower(substr($time, -3)) == 'min')
			$this->timestamp = strtotime('+'.substr($time, 0, -3).' minutes');
		else if($time == 'sofort' || $time == "0")
			$this->timestamp = time();
		else 
			$this->timestamp = Helper::getTimestamp($time);
			
		//$this->timeWritten = date('d.m.Y G:i', $this->timestamp);
			
		$this->traction = $traction;
	}
	
	public function getRoute() {
		return $this->route;
	}
	
	public function getDestination() {
		return $this->destination;
	}
	
	public function getDirection() {
		return $this->direction;
	}
	
	public function getLowfloor() {
		return $this->lowfloor;
	}
	
	public function getRealtime() {
		return $this->realtime;
	}
	
	public function getStopPosition() {
		return $this->stopPosition;
	}
	
	public function getTime() {
		return $this->time;
	}
	
	public function getTimestamp() {
		return $this->timestamp;
	}
	
	public function getTraction() {
		return $this->traction;
	}
}
?>