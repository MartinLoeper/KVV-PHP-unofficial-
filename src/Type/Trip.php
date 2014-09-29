<?php
namespace KVV\Type;

class Trip {
	private $name;
	private $interval;
	private $duration;
	private $with;
	private $changes;
	private $sections;
	private $requestInfo;
	private $efa;
	
	public function __construct($requestInfo, $name, $interval, $duration, $with, $changes, $sections, \KVV\EFA &$efa) {
		$this->name = &$name;
		$this->interval = &$interval;
		$this->duration = &$duration;
		$this->with  = &$with;
		$this->changes = &$changes;
		$this->sections = &$sections;
		$this->requestInfo = &$requestInfo;
		$this->efa = &$efa;
	}
	
	public function getRequestedOrigin() {
		return $this->requestInfo['origin'];
	}
	
	public function getRequestedDestination() {
		return $this->requestInfo['destination'];
	}
	
	public function getRequestInfo() {
		return $this->requestInfo;
	}
	
	public function getNext($new_only = TRUE) {
		return $this->efa->getNext($new_only, $this->requestInfo['timestamp'], $this->requestInfo['language']);
	}
	
	public function getPrevious($new_only = TRUE) {
		return $this->efa->getPrevious($new_only, $this->requestInfo['timestamp'], $this->requestInfo['language']);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getInterval() {
		$interval = explode('&#150;', $this->interval);
		return array(Helper::getTimestamp($interval[0], $this->requestInfo['timestamp']), Helper::getTimestamp($interval[1], $this->requestInfo['timestamp']));
	}
	
	
	public function getDuration() {
		$min = substr($this->duration, -2);
		$min += (int)substr($this->duration, 0, 2) * 60;
		return $min;
	}
	
	public function getWith() {
		$temp = explode(':', $this->with);
		$routes = explode(',', $temp[1]);
		
		return $routes;
	}
	
	public function getChanges() {
		$changes = explode(':', $this->changes);
		return trim($changes[1]);
	}
	
	public function getSections() {
		return $this->sections;
	}
}
?>