<?php
namespace KVV\Type;

class Helper {
	/**
		Takes a timestamp which is formed like "0:11" and calculates the unix timestamp based on the current time.
		@param $str time-string to convert
		@param (optional) $timestamp the reference value - default: time()
		
		@return unix timestamp of $str
	**/
	public static function getTimestamp($str, $timestamp = 0) {
		if($timestamp == 0)
			$timestamp = time();
			
		$temp = explode(':', $str);
		$h = $temp[0];
		$min = $temp[1];
		return strtotime($str, ($h < date('G', $timestamp) || ($h <= date('G', $timestamp) && $min < date('i', $timestamp)) ? strtotime('tomorrow') : $timestamp));
	}
}
?>