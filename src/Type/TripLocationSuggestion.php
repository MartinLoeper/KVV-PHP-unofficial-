<?php
namespace KVV\Type;

class TripLocationSuggestion {
	private $sessionID;
	private $requestID;
	private $origin_stations;
	private $destination_stations;
	private $requestInfo;
	private $efa;
	
	public function __construct($requestInfo, $sessionID, $requestID, $origin_stations, $destination_stations, &$efa) {
		$this->requestInfo = &$requestInfo;
		$this->sessionID = &$sessionID;
		$this->requestID = &$requestID;
		$this->origin_stations = &$origin_stations;
		$this->destination_stations = &$destination_stations;
		$this->efa = &$efa;
	}
	
	public function getRequestInfo() {
		return $this->requestInfo;
	}
	
	public function getSessionId() {
		return $this->sessionID;
	}
	
	public function getRequestId() {
		return $this->requestID;
	}
	
	public function getOriginSuggestions() {
		return $this->origin_stations;
	}
	
	public function getDestinationSuggestions() {
		return $this->destination_stations;
	}
	
	/**
		Executes a search request against the KVV endpoint using the given parameters as suggestion array index.
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@param $start the index of $origin_stations to use
		@param $end the index of $destination_stations to use
		
		@return tripRequest object of type \KVV\Type\TripRequest
	*/
	public function utilize($start, $end) {
		if($start >= count($this->origin_stations))
			throw new \Exception("Index Out Of Bounds (origin_stations)! ".$start."/".(count($this->origin_stations)-1), 500);
			
		if($end >= count($this->destination_stations))
			throw new \Exception("Index Out Of Bounds! (destination_stations)! ".$end."/".(count($this->destination_stations)-1), 500);
		
		$this->efa->setSessionId($this->getSessionId());
		$this->efa->setRequestId($this->getRequestId());

		return $this->efa->precise_search($this->origin_stations[$start], $this->destination_stations[$end], $this->requestInfo['timestamp'], $this->requestInfo['language']);
	}
}
?>