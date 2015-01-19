<?php
namespace KVV;

/**
	This class retrieves information from the official mobile KVV webservice (http://info.kvv.de/). 
	Nevertheless it is based on unofficial programming interfaces which are therefore subject to change without notice.
**/
class EFA {
	const KVV_SEARCH_ENGINE = 'ix_efa_searchService';
	const ACTION_STOP_SEARCH = 'stopRequest';
	const ACTION_TRIP_SEARCH = 'tripRequest';
	const ACTION_TRIP_SCROLL = 'tripScroll';
	const WEB_ROOT = 'http://info.kvv.de/';
	const URI_BASE = 'index.php';
	const FIXED_TIME_CONSTANT = 123456789;
	const TRAVEL_TYPE_DEPARTURE = 'dep';
	const TRAVEL_TYPE_ARRIVAL = 'arr';
	const TRAVEL_CHANGE_SPEED_SLOW = 'slow';
	const TRAVEL_CHANGE_SPEED_NORMAL = 'normal';
	const TRAVEL_CHANGE_SPEED_FAST = 'fast';
	const LANGUAGE_GERMAN = '';
	const LANGUAGE_ENGLISH = 1;
	const LANGUAGE_FRENCH = 2;
	const LANGUAGE_ITALIAN = 3;
	const LANGUAGE_TURKISH = 4;
	private $client;
	private $cache_enabled;
	private $current_sessionID = 0;
	private $current_requestID = 0;
	private $current_item_number = 0;
	private $last_origin = '';
	private $last_destination = '';
	
	/**
		Creates a new efa object prepared for queries.
		@param $cache_enabled (optional) if set true, the requests are cached otherwise not (default:false)
	**/
	public function __construct($cache_enabled = FALSE) {
		$this->client = new \GuzzleHttp\Client();
		$this->cache_enabled = &$cache_enabled;
	}
	
	public function serialize() {
		return "cache_enabled=".$this->cache_enabled."&current_session_id=".$this->current_sessionID."&current_request_id=".$this->current_requestID."&current_item_number=".$this->current_item_number."&last_destination=".$this->last_destination."&last_origin=".$this->last_origin;
	}
	
	public function init(array $pairs) {
		$this->current_sessionID = $pairs["current_session_id"];
		$this->current_request_id = $pairs["current_request_id"];
		$this->current_item_number = $pairs["current_item_number"];
		$this->last_destination = $pairs["last_destination"];
		$this->last_origin  = $pairs["last_origin"];
	}
	
	public static function populate(array $pairs) {
		$obj = new EFA($pairs["cache_enabled"]);
		$obj->init($pairs);
		
		return $obj;
	}
	
	public function setSessionId($id) {
		$this->current_sessionID = $id;
	}
	
	public function setRequestId($id) {
		$this->current_requestID = $id;
	}
	
	public function clearSessionData() {
		$this->current_sessionID = 0;
		$this->current_requestID = 0;
	}
	
	/**
		This method is invoked from a TripLocationSuggestion object to do a precise match.
	**/
	public function precise_search($origin, $destination, $timestamp, $lang, $travel_type=EFA::TRAVEL_TYPE_DEPARTURE, $no_solid_stairs=FALSE, $no_escalators=FALSE, $no_elevators=FALSE, $low_platform_vehicle=FALSE, $wheelchair=FALSE, $change_speed=EFA::TRAVEL_CHANGE_SPEED_NORMAL, $lang=EFA::LANGUAGE_GERMAN) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$query = ['eID' => self::KVV_SEARCH_ENGINE,
						'ix_action' => self::ACTION_TRIP_SEARCH,
						'sessionID' => $this->current_sessionID,
						'requestID' => $this->current_requestID,
						'ix_originValue' => urlencode($origin[1]),
						'ix_originSessionValue' => urlencode($origin[0]),
						'ix_destinationSessionValue' => urlencode($destination[0]),
						'ix_originText' => urlencode($origin[1]),
						'type_origin' => 'any',
						'ix_destinationValue' => urlencode($destination[1]),
						'type_destination' => 'any',
						'sessionID' => $this->current_sessionID,
						'requestID' => $this->current_requestID,
						'ix_destinationText' => urlencode($destination[1]),
						'ix_date' => date('d.m.Y', $timestamp),
						'ix_hour' => date('G', $timestamp),
						'ix_minute' => date('i', $timestamp),
						'ix_travelType' => $travel_type,
						'ix_noSolidStairs' => (int)$no_solid_stairs,
						'ix_noEscalators' => (int)$no_escalators,
						'ix_noElevators' => (int)$no_elevators,
						'ix_lowPlatformVhcl' => (int)$low_platform_vehicle,
						'ix_wheelchair' => (int)$wheelchair,
						'ix_changeSpeed' => $change_speed,
						'ix_language' => $lang,	
						'_' => $randomizer
			];
			
		if(count(explode(':', $origin[0])) > 1)
			$query['nameState_origin'] = 'list';
			
		if(count(explode(':', $destination[0])) > 1)
			$query['nameState_destination'] = 'list';
		
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE, [
			'exceptions' => true,
			'query' => $query
		]);
		
		return $this->process_search($res, $origin, $destination, $timestamp, $lang);
	}
	
	public function process_search(&$res, $origin, $destination, $timestamp, $lang) {
		if($res->getStatusCode() == 200) {
			$this->last_origin = $origin;
			$this->last_destination = $destination;
			$raw = (string)$res->getBody();
			$html = \str_get_dom($raw);
			$this->current_sessionID = $html('input#sessionID', 0)->value;
			$this->current_requestID = $html('input#requestID', 0)->value;
			if(trim(strtok($raw, "\n")) == '<!-- ix_chooser.html  DO NOT CHANGE THIS FIRST LINE: parsed in javascript !!-->') {
				// we have to select once again...
				$origin_stations = array();
				$destination_stations = array();
			
				foreach($html('select#ix_origin option') AS &$station_origin) {
					//$origin_stations[] = array($station_origin->getInnerText() => $station_origin->value);
					$origin_stations[] = array($station_origin->value, trim(preg_replace("/\[[^)]+\]/", "", $station_origin->getInnerText())));
				}
				
				foreach($html('select#ix_destination option') AS &$station_destination) {
					//$destination_stations[] = array($station_destination->getInnerText() => $station_destination->value);
					$destination_stations[] = array($station_destination->value, trim(preg_replace("/\[[^)]+\]/", "", $station_destination->getInnerText())));
				}
				
				$suggestion = new \KVV\Type\TripLocationSuggestion(array('timestamp' => $timestamp, 'language' => $lang, 'origin' => $origin, 'destination' => $destination), $this->current_sessionID, $this->current_requestID, $origin_stations, $destination_stations, $this);
				return new \KVV\Type\TripRequest(false, $suggestion, $this);
			}
			else {
				// return trips
				$trips = &$this->parseTrips($html, $origin, $destination, $timestamp, $lang);
				
				if($trips->isCompleted())
					$this->current_item_number = count($trips->getInfo());
				
				return $trips;
			}
		}

		return null; 
	}
	
	/**
		Retrieves a trip for given origin, destination and time information.
		Heads Up! Always catch \KVV\RouteNotFoundException!
		@throws \GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@throws \KVV\RouteNotFoundException if no route is found (i.e. stops do not exist)
		@param $origin The name of the the original
		@param $destination The name of the destination
		@param $timestamp The UNIX timestamp for which trips should be found.
		@param $travel_type (optional) specify if the given timestamp should be the arrival time ('arr') or departure time ('dep') - default: dep
		@param $no_solid_stairs (optional) if no fixed stairs should be used set this TRUE - default: false
		@param $no_escalators (optional) if no escalators should be used set this TRUE - default: false
		@param $no_elevators (optional) if lifts should not be used set this TRUE - default: false
		@param $low_platform_vehicle (optional) if low-floor vehicles are required set this TRUE - default: false
		@param $wheelchair (optional) if vehicles with wheelchair lift or level entrances are required set this TRUE - default: false
		@param $change_speed (optional) the walking time: choose between (string) slow, normal, fast - default:normal
		@param $lang (optional) the language to use (null=German,1=English,2=French,3=Italian,4=Turkish) - default:German
		
		@return tripRequest object of type \KVV\Type\TripRequest
	**/
	public function search($origin, $destination, $timestamp, $travel_type=EFA::TRAVEL_TYPE_DEPARTURE, $no_solid_stairs=FALSE, $no_escalators=FALSE, $no_elevators=FALSE, $low_platform_vehicle=FALSE, $wheelchair=FALSE, $change_speed=EFA::TRAVEL_CHANGE_SPEED_NORMAL, $lang=EFA::LANGUAGE_GERMAN) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE, [
			'exceptions' => true,
			'query' => ['eID' => self::KVV_SEARCH_ENGINE,
						'ix_action' => self::ACTION_TRIP_SEARCH,
						'sessionID' => $this->current_sessionID,
						'requestID' => $this->current_requestID,
						'ix_originValue' => $origin,
						'ix_originText' => $origin,
						'type_origin' => 'any',
						'ix_destinationValue' => $destination,
						'type_destination' => 'any',
						'ix_destinationText' => $destination,
						'ix_date' => date('d.m.Y', $timestamp),
						'ix_hour' => date('G', $timestamp),
						'ix_minute' => date('i', $timestamp),
						'ix_travelType' => $travel_type,
						'ix_noSolidStairs' => (int)$no_solid_stairs,
						'ix_noEscalators' => (int)$no_escalators,
						'ix_noElevators' => (int)$no_elevators,
						'ix_lowPlatformVhcl' => (int)$low_platform_vehicle,
						'ix_wheelchair' => (int)$wheelchair,
						'ix_changeSpeed' => $change_speed,
						'ix_language' => $lang,	
						'_' => $randomizer
			]
		]);

		return $this->process_search($res, $origin, $destination, $timestamp, $lang);
	}
	
	/**
		Parses the html code from KVV to obtain trip information.
		@throws \KVV\RouteNotFoundException if no route is found (i.e. stops do not exist)
		Heads Up! This is one of the most cpu intensive routines and should be optimized in the future.
	**/
	private function parseTrips($html, $origin, $destination, $timestamp, $lang) {
		$trips = array();
		$index = 0;
		$trip_obj = $html('ul#fahrten > span');
	
		if(count($trip_obj) == 0)
			throw new RouteNotFoundException();
			
		foreach($trip_obj AS &$fahrt) {
			$index++;
			$name = $fahrt('.tab1', 0)->getInnerText();
			$interval = $fahrt('.tab2 strong', 0)->getInnerText();
			$duration = substr($fahrt('.tab4', 0)->getInnerText(), -5);
			$with = $fahrt('.tab3', 0)->getInnerText();
			$changes = $fahrt('.tab4', 1)->getInnerText();
			$sections = array();
			foreach($fahrt('ul.fahrtliste') AS &$fahrtinfo) {
				$details = &$fahrtinfo('li', 0)->getInnerText();
				$infos = array();
				foreach(explode('<br />', $details) AS &$info) {
					$val = &trim(strip_tags($info));
					if($val != '')
						$infos[] = $val;
				}
				$beginning = $fahrtinfo('li.fahrtenlist', 0);
				$origin_time = $beginning('.tab1 strong', 0)->getInnerText();
				$origin_place = $beginning('.tab3 strong', 0)->getInnerText();
				
				$end = $fahrtinfo('li.fahrtenlistende', 0);
				$destination_time = $end('.tab1 strong', 0)->getInnerText();
				$destination_place = $end('.tab3 strong', 0)->getInnerText();
				
				$stations_obj = $fahrtinfo('span.toggle li');
				$stations = array();
				if($stations_obj != NULL) {
					foreach($stations_obj AS &$station) {
						$time = $station('span', 0)->getInnerText();
						$stations[] = new \KVV\Type\Station(trim(str_replace($time, "", strip_tags($station->getInnerText()))), $time, $timestamp);
					}
				}
				$sections[] = new \KVV\Type\Section($infos, $origin_time, $origin_place, $destination_time, $destination_place, $stations, $timestamp);
			}

			$trips[] = new \KVV\Type\Trip(array('timestamp' => $timestamp, 'language' => $lang, 'origin' => $origin, 'destination' => $destination), $name, $interval, $duration, $with, $changes, $sections, $this);	
		}

		
		return new \KVV\Type\TripRequest(true, $trips, $this);	
	}
	
	public function getNext($new_only = TRUE, $timestamp=-1, $lang='') {
		if($timestamp == -1)
			$timestamp = time();
			
		if($this->last_origin == '')
			throw new \Exception("You cannot call getNext() on an object where search() or populate() was no invoked before!", 500);
			
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE, [
			'exceptions' => true,
			'query' => ['eID' => self::KVV_SEARCH_ENGINE,
						'ix_action' => self::ACTION_TRIP_SCROLL,
						'command' => 'tripNext',
						'sessionID' => $this->current_sessionID,
						'requestID' => $this->current_requestID,
						'ix_date' => date('d.m.Y', $timestamp),
						'ix_hour' => date('G', $timestamp),
						'ix_minute' => date('i', $timestamp),
						'ix_language' => $lang,	
						'_' => $randomizer
			]
		]);	
		
		if($res->getStatusCode() == 200) {
			$html = \str_get_dom((string)$res->getBody());
			$trips = &$this->parseTrips($html, $this->last_origin, $this->last_destination, $timestamp, $lang)->getInfo();
			
			$size = count($trips);
			if($new_only) {
				$trips = array_slice($trips, $this->current_item_number);
			}
			$this->current_item_number = $size;
			
			return $trips;
		}
		
		return null;
	}
	
	public function getPrevious($new_only = TRUE, $timestamp=-1, $lang='') {
		if($timestamp == -1)
			$timestamp = time();
			
		if($this->last_origin == '')
			throw new \Exception("You cannot call getPrevious() on an object where search() or populate() was no invoked before!", 500);

		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE, [
			'exceptions' => true,
			'query' => ['eID' => self::KVV_SEARCH_ENGINE,
						'ix_action' => self::ACTION_TRIP_SCROLL,
						'command' => 'tripPrev',
						'sessionID' => $this->current_sessionID,
						'requestID' => $this->current_requestID,
						'ix_date' => date('d.m.Y', $timestamp),
						'ix_hour' => date('G', $timestamp),
						'ix_minute' => date('i', $timestamp),
						'ix_language' => $lang,	
						'_' => $randomizer
			]
		]);	
		
		if($res->getStatusCode() == 200) {
			$html = \str_get_dom((string)$res->getBody());
			$trips = $this->parseTrips($html, $this->last_origin, $this->last_destination, $timestamp, $lang)->getInfo();
			
			$size = count($trips);
			if($new_only) {
				array_splice($trips, $size-$this->current_item_number);
			}
			$this->current_item_number = $size;
			
			return $trips;
		}
		
		return null;	
	}
	
	/**
		Returns an array of type \KVV\Type\location.
		
		@param $q The request to search for (i.e name of a station, famous location or a street)
		
		@return array with places
	**/
	public function autocomplete($q) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE, [
			'exceptions' => true,
			'query' => ['eID' => self::KVV_SEARCH_ENGINE,
						'ix_action' => 'stopRequest',
						'name_sf' => $q,
						'_' => $randomizer
			]
		]);
		
		if($res->getStatusCode() == 200) {
			$results = array();
			foreach($res->json()['stopFinder']['points'] AS &$value) {
				$results[] = new \KVV\Type\Location($value['name'], $value['anyType']);
			}		
			return $results;
		}

		return array(); 		
	}
}
?>