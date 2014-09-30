<?php
namespace KVV\Type;

class TripRequest {
	private $success;
	private $info;
	private $efa;
	
	/**
		@param $success true if routes are provided, false if further selection is needed
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
		Returns next trips using a custom logic (faster than \KVV\EFA getNext()).
		Heads Up! Always catch \KVV\RouteNotFoundException!
		@throws \GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@throws \KVV\RouteNotFoundException if no route is found (i.e. stops do not exist)
		
		@param $no_solid_stairs (optional) if no fixed stairs should be used set this TRUE - default: false
		@param $no_escalators (optional) if no escalators should be used set this TRUE - default: false
		@param $no_elevators (optional) if lifts should not be used set this TRUE - default: false
		@param $low_platform_vehicle (optional) if low-floor vehicles are required set this TRUE - default: false
		@param $wheelchair (optional) if vehicles with wheelchair lift or level entrances are required set this TRUE - default: false
		@param $change_speed (optional) the walking time: choose between (string) slow, normal, fast - default:normal

		@return an array of objects with type \KVV\Type\TripRequest
	**/
	public function getNext($no_solid_stairs=FALSE, $no_escalators=FALSE, $no_elevators=FALSE, $low_platform_vehicle=FALSE, $wheelchair=FALSE, $change_speed=\KVV\EFA::TRAVEL_CHANGE_SPEED_NORMAL) {
		if(!$this->isCompleted())
			throw new \Exception('Cannot call next() on a TripRequest-Object that  is not ready! Check with isCompleted() before!', 500);
	
		if(count($this->getInfo()) == 0)
			throw new \KVV\RouteNotFoundException();
				
		$last = &$this->getInfo()[count($this->getInfo())-1];
		
		$next_trips_request = $this->efa->search($this->getInfo()[0]->getRequestInfo()["origin"], $this->getInfo()[0]->getRequestInfo()["destination"], $last->getInterval()[1], \KVV\EFA::TRAVEL_TYPE_DEPARTURE, $no_solid_stairs, $no_escalators, $no_elevators, $low_platform_vehicle, $wheelchair, $change_speed, $this->getInfo()[0]->getRequestInfo()["language"]);
		$next_trips = $next_trips_request->getInfo();
		
		/* remove duplicates */
		foreach($next_trips AS $index=>$previous_trip) {
			foreach($this->getInfo() AS &$original) {
				if($original->equals($previous_trip))
					unset($next_trips[$index]);
			}
		} 
		
		$next_trips_request->setInfo($next_trips);
		
		return $next_trips_request;
	}
	
	public function setInfo($info) {
		$this->info = $info;
	}
	
	/**
		Returns previous trips using a custom logic (faster than \KVV\EFA getPrevious()).
		Heads Up! Always catch \KVV\RouteNotFoundException!
		@throws \GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@throws \KVV\RouteNotFoundException if no route is found (i.e. stops do not exist)
		
		@param $no_solid_stairs (optional) if no fixed stairs should be used set this TRUE - default: false
		@param $no_escalators (optional) if no escalators should be used set this TRUE - default: false
		@param $no_elevators (optional) if lifts should not be used set this TRUE - default: false
		@param $low_platform_vehicle (optional) if low-floor vehicles are required set this TRUE - default: false
		@param $wheelchair (optional) if vehicles with wheelchair lift or level entrances are required set this TRUE - default: false
		@param $change_speed (optional) the walking time: choose between (string) slow, normal, fast - default:normal

		@return an array of objects with type \KVV\Type\TripRequest
	**/
	public function getPrevious($no_solid_stairs=FALSE, $no_escalators=FALSE, $no_elevators=FALSE, $low_platform_vehicle=FALSE, $wheelchair=FALSE, $change_speed=\KVV\EFA::TRAVEL_CHANGE_SPEED_NORMAL) {
		if(!$this->isCompleted())
			throw new \Exception('Cannot call next() on a TripRequest-Object that  is not ready! Check with isCompleted() before!', 500);	
		
		if(count($this->getInfo()) == 0)
			throw new \KVV\RouteNotFoundException();
				
		$first = &$this->getInfo()[0];
		
		$previous_trips_request = $this->efa->search($this->getInfo()[0]->getRequestInfo()["origin"], $this->getInfo()[0]->getRequestInfo()["destination"], $first->getInterval()[0], \KVV\EFA::TRAVEL_TYPE_ARRIVAL, $no_solid_stairs, $no_escalators, $no_elevators, $low_platform_vehicle, $wheelchair, $change_speed, $this->getInfo()[0]->getRequestInfo()["language"]);
		$previous_trips = $previous_trips_request->getInfo();
		
		/* remove duplicates */
		foreach($previous_trips AS $index=>$previous_trip) {
			foreach($this->getInfo() AS &$original) {
				if($original->equals($previous_trip))
					unset($previous_trips[$index]);
			}
		}
		
		$previous_trips_request->setInfo($previous_trips);
		
		return $previous_trips_request;
	}
	
	/**
		Generates a \KVV\Type\TripRequest object out of \KVV\Type\TripLocationSuggestion, using the first matches of suggested stops.
		If the dataset is already containing a \KVV\Type\Trip object, it is returned being wrapped into a \KVV\Type\TripRequest object.
		
		@return an object of type \KVV\Type\TripRequest
	**/
	public function getFirstMatch() {			
		if($this->isCompleted())
			return $this;
		else {
			$tripSuggestion = $this->getInfo();
			return $tripSuggestion->utilize(0, 0);
		}
	}
	
	/**
		Custom method which tries to find the most probable match (better than getFirstMatch() but also less performant).

		@return an object of type \KVV\Type\TripRequest
	**/
	public function getMostProbableMatch() {
		$suggestions = &$this->getInfo();
		$suggestions_origin = &$suggestions->getOriginSuggestions();
		$suggestions_destination = &$suggestions->getDestinationSuggestions();
		$searchphrase_origin = $suggestions->getRequestInfo()['origin'];
		$searchphrase_destination =$suggestions->getRequestInfo()['destination'];
		
		/* origin */
		$most_probable_index = 0;
		$highest_probability = 0;
		foreach($suggestions_origin AS $index=>$suggestion) {
			$current_probability = 0;
			similar_text($searchphrase_origin, $suggestion, $current_probability);
			if($current_probability > $highest_probability) {
				$highest_probability = $current_probability;
				$most_probable_index = $index;
			}
		}
		
		$origin_index = $most_probable_index;
				
		/* destination */
		$most_probable_index = 0;
		$highest_probability = 0;
		foreach($suggestions_destination AS $index=>$suggestion) {
			$current_probability = 0;
			similar_text($searchphrase_destination, $suggestion, $current_probability);
			if($current_probability > $highest_probability) {
				$highest_probability = $current_probability;
				$most_probable_index = $index;
			}
		}
		
		$destination_index = $most_probable_index;
				
		return $suggestions->utilize($origin_index, $destination_index);
	}
}
?>