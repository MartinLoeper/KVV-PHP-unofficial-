<?php
namespace KVV\Type;

class TripRequest {
	private $success;
	private $info;
	private $efa;
	
	/**
		@param $success true if routes are provided, false if further seletion is needed
		@param $info contains an object of type \KVV\Type\TripLocationSuggestion if $success is false, otherwise type is \KVV\Type\Trip
		@param $efa the reference to the corresponding \KVV\Efa object
	**/
	public function __construct($success, $info, \KVV\EFA &$efa) {
		$this->success = $success;
		$this->info = $info;
		$this->efa = &$efa;
	}
	
	public function getInfo() {
		return $this->info;
	}
	
	public function isCompleted() {
		return $this->success;
	}
	
	/**
		Generates a \KVV\Type\Trip object out of \KVV\Type\TripLocationSuggestion, using the first matches of suggested stops.
		If the dataset is already containing a \KVV\Type\Trip object, it is returned.
		
		@return an object of type \KVV\Type\Trip
	**/
	public function getFirstMatch() {			
		if($this->isCompleted())
			return $this->getInfo();
		else {
			$tripSuggestion = $this->getInfo();
			return $tripSuggestion->utilize($this->efa, $tripSuggestion->getRequestInfo()['timestamp'], 0, 0)->getInfo();
		}
	}
}
?>