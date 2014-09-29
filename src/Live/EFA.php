<?php
namespace KVV\Live;

/**
	This class retrieves live information from the official mobile KVV webservice (http://http://live.kvv.de/). 
	Nevertheless it is based on unofficial programming interfaces which are therefore subject to change without notice.
**/
class EFA {
	private $client;
	private $cache_enabled;
	const WEB_ROOT = 'http://live.kvv.de/';
	const URI_BASE = 'webapp/';
	const KVV_KEY = '377d840e54b59adbe53608ba1aad70e8';
	const FIXED_TIME_CONSTANT = 123456789;
	
	/**
		Creates a new efa object prepared for queries.
		@param $cache_enabled (optional) if set true, the requests are cached otherwise not (default:false)
	**/
	public function __construct($cache_enabled = FALSE) {
		$this->client = new \GuzzleHttp\Client();
		$this->cache_enabled = &$cache_enabled;
	}
	
	/**
		Searches stops by name.
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@param $q The name of the stop to search for.
		
		@return array with objects of type \KVV\Type\Stop
	**/
	public function search($q) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."stops/byname/".urlencode($q), [
			'exceptions' => true,
			'query' => ['key' => self::KVV_KEY,
						'_' => $randomizer
			]
		]);
		
		if($res->getStatusCode() == 200) {
			$stops = array();
			foreach($res->json()['stops'] AS &$val) {
				$stops[] = new \KVV\Type\Stop($val['id'], $val['name'], $val['lat'], $val['lon']);
			}
			
			return $stops;
		}
		
		return null;
	}
	
	/**
		Returns information about given stop id.
		Heads Up! Exception of type \GuzzleHttp\Exception\ClientException is thrown if an invalid id is provided.
		@throws \KVV\Live\NoLiveDataAccessException If an unknown $stopId is provided or the given id has no live data access
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@param $q The id of the stop to search for.
		
		@return objects of type \KVV\Type\Stop
	**/
	public function searchById($q) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		try {
			$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."stops/bystop/".urlencode($q), [
				'exceptions' => true,
				'query' => ['key' => self::KVV_KEY,
							'_' => $randomizer
				]
			]);
		}
		catch(\GuzzleHttp\Exception\ClientException $e) {
			throw new NoLiveDataAccessException();
		}
		
		if($res->getStatusCode() == 200) {
			$val = $res->json();
			$stops = new \KVV\Type\Stop($val['id'], $val['name'], $val['lat'], $val['lon']);
			
			return $stops;
		}
		
		return null;
	}
	
	/**
		Returns information about stops near the given lat/lon position.
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@param $lat the latitude to search around
		@param $lon the longitude to search around
		
		@return objects of type \KVV\Type\StopExt
	**/
	public function searchByLatLon($lat, $lon) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."stops/bylatlon/".$lat."/".$lon, [
			'exceptions' => true,
			'query' => ['key' => self::KVV_KEY,
						'_' => $randomizer
			]
		]);
		
		if($res->getStatusCode() == 200) {
			$stops = array();
			foreach($res->json()['stops'] AS &$val) {
				$stops[] = new \KVV\Type\StopExt($val['id'], $val['name'], $val['lat'], $val['lon'], $val['distance']);
			}
			
			return $stops;
		}
		
		return null;
	}
	
	/**
		Gets departures for the given stop id.
		Heads Up! Always catch \KVV\Live\NoLiveDataAccessException - Remember that only important stations (mostly tram) have live data provided
		@throws \GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@throws \KVV\Live\NoLiveDataAccessException If an unknown $stopId is provided or the given id has no live data access
		@param $stopId the stop id
		@param $maxInfos (optional) the max number of departures to show - 10 is max - default: 10
		
		@return array with objects of type \KVV\Type\Departure
	**/
	public function getDepartures($stopId, $maxInfos=10) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		try {
			$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."departures/bystop/".urlencode($stopId), [
				'exceptions' => true,
				'query' => ['key' => self::KVV_KEY,
							'maxInfos' => $maxInfos,
							'_' => $randomizer
				]
			]);
		}
		catch(\GuzzleHttp\Exception\ClientException $e) {
			throw new NoLiveDataAccessException();
		}
		
		if($res->getStatusCode() == 200) {
			$departures = array();
			foreach($res->json()['departures'] AS &$val) {
				$departures[] = new \KVV\Type\Departure($val['route'], $val['destination'], $val['direction'], $val['lowfloor'], $val['realtime'], $val['stopPosition'], $val['time'], $val['traction']);
			}
			
			return $departures;
		}
		
		return null;
	}
	
	/**
		Custom method which tries to find the most probable match for an array of $suggestions based on the $searchphrase.
		@param $suggestions an array with objects of type \KVV\Type\Stop or \KVV\Type\StopExt
		@param $searchphrase a string with should be compared to the objects in the array
		
		@return the \KVV\Type\Stop(Ext) object with the highest probability
	**/
	public static function getMostProbableMatch(array $suggestions, $searchphrase) {
		$most_probable;
		$highest_probability = 0;
		foreach($suggestions AS &$suggestion) {
			$current_probability = 0;
			similar_text($searchphrase, $suggestion->getName(), $current_probability);
			if($current_probability > $highest_probability) {
				$highest_probability = $current_probability;
				$most_probable = $suggestion;
			}
		}
		
		return $most_probable;
	}
	
	/**
		Gets departures for the given stop id and route.
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library
		@param $stopId the stop id (i.e de:8212:3)
		@param $route the route (i.e S2)
		@param $maxInfos (optional) the max number of departures to show - 10 is max - default: 10
		
		@return array with objects of type \KVV\Type\Departure
	**/
	public function getDeparturesByRoute($stopId, $route, $maxInfos=10) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."departures/byroute/".urlencode($route)."/".urlencode($stopId), [
			'exceptions' => true,
			'query' => ['key' => self::KVV_KEY,
						'maxInfos' => $maxInfos,
						'_' => $randomizer
			]
		]);
		
		if($res->getStatusCode() == 200) {
			$departures = array();
			foreach($res->json()['departures'] AS &$val) {
				$departures[] = new \KVV\Type\Departure($val['route'], $val['destination'], $val['direction'], $val['lowfloor'], $val['realtime'], $val['stopPosition'], $val['time'], $val['traction']);
			}
			
			return $departures;
		}
		
		return null;
	}

	/**
		Returns information whether the given stop is part of the given route.
		Make sure that $stopId and $route really exist otherwise this method will return false!
		@throws GuzzleHttp\Exception\TransferException All kinds of Exceptions by Guzzle HTTP Library except of \GuzzleHttp\Exception\ClientException
		@param $stopId the stop id (i.e de:8212:3)
		@param $route the route (i.e S2)
		@param $maxInfos (optional) the max number of departures to show - 10 is max - default: 10
		
		@return true if stop is part of route, otherwise false
	**/
	public function isStopPartOfRoute($stopId, $route, $maxInfos=10) {
		$randomizer = (!$this->cache_enabled) ? time() : self::FIXED_TIME_CONSTANT;
		try {
			$res = $this->client->get(self::WEB_ROOT.self::URI_BASE."departures/byroute/".urlencode($route)."/".urlencode($stopId), [
				'exceptions' => true,
				'query' => ['key' => self::KVV_KEY,
							'maxInfos' => $maxInfos,
							'_' => $randomizer
				]
			]);
		}
		catch(\GuzzleHttp\Exception\ClientException $e) {
			return false;
		}
		
		if($res->getStatusCode() == 200) {
			return true;
		}
		
		return false;
	}	
}
?>