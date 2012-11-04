<?php

class AntraegeUtils
{

	/**
	 * @param string $input
	 * @return int
	 */
	public static function date_iso2timestamp($input)
	{
		$x    = explode(" ", $input);
		$date = array_map("IntVal", explode("-", $x[0]));

		if (count($x) == 2) $time = array_map("IntVal", explode(":", $x[1]));
		else $time = array(0, 0, 0);

		return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
	}


}