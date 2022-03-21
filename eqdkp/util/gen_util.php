<?php

namespace eqdkp\util;

use \DateTime;
use \DateTimeZone;

class gen_util
{

	public static function getTimeOptions($first_value = -1, $selected = -1)
	{
		$today = strtotime('00:00');
		$option = '<option value="-1">Select a Time</option>';
		$i = 0;
		if ($first_value > -1) {
			$i = $first_value;
		}
		$end = $i + 24;
		for($i; $i < $end; $i+=.5) {
			$option .= '<option value="' . $i . '"'.($selected == $i ? ' selected ' : '').'>' . self::formatTime($i) . '</option>';
		}
		return $option;
	}

	public static function formatTime($value)
	{
		if ($value < 0) {
			return -1;
		}
		$today = strtotime('00:00');
		return date("h:ia", ($value*3600)+$today);
	}

	// requires time_str in the format hhmmss
	public static function convTimeStr($time_str)
	{
		$time = 0.0;
		if (strlen($time_str) != 6) {
			return $time;
		}
		$time_parts = str_split($time_str, 2);
		$time = (float) $time_parts[0];
		$min = (int) $time_parts[1];
		if ($min > 15 && $min <= 45) {
			$time += .5;
		} elseif ($min > 45) {
			$time += 1.0;
			if ($time > 23.5) {
				$time = 0.0;
			}
		}
		return $time;
	}

	public static function countTicks($time_array, $end_now = 0)
	{
		$ticks = 0;
		$now = new DateTime("now", new DateTimeZone("US/Eastern"));
		while(!empty($time_array)) {
			$start_time = array_shift($time_array);
			$end_time = array_shift($time_array);
			if (!$end_time) {
				if ($end_now) {
					$end_time = self::convTimeStr($now->format('His'));
					if ($end_time < $start_time) {
						// raid has gone past midnight
						$end_time += 24;
					}
				} else {
					$end_time = $start_time;
				}
			}
			$ticks += (($end_time - $start_time) / .5) + 1;
		}
		return $ticks;
	}

	/**
		* Calculates earned DKP for a given time array
		*
		* ppt = points per tick (might be different for mains vs seconds or certain events)
		* time_array = an array of floats paired off as start/end
		* raid_start = float of the hour.min of start of raid
		* end_now = use current time as end of raid (for calculating DKP mid-raid)
		* raid_end = float of the hour.min of the end of raid
		*
		* if end_now is set, bonus DKP for "full raid" will not be added in
		* raid_start/end are used to determine if member earns "full raid" bonus DKP
		*/
	public static function calcDKP($ppt, $time_array, $raid_start, $end_now = 0, $raid_end = -1)
	{
		$dkp = 0;
		$ticks = self::countTicks($time_array, $end_now);

		$dkp = $ticks * $ppt;
		// figure out if they were present for the whole raid
		if (!$end_now && $raid_end > -1) {
			$first_tick = $time_array[0];
			$last_tick = $time_array[1]; // can't have gaps to get credit
			if ($first_tick == $raid_start && $last_tick == $raid_end) {
				$dkp += $ppt;
			}
		}
		return $dkp;
	}
}

?>
