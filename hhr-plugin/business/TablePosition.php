<?php

class TablePosition
{
	private static $positions = array(
		2 => array(
			'sb', 'bb'
		),
		3 => array(
			'btn', 'sb', 'bb',
		),
		4 => array(
			'btn', 'sb', 'bb', 'co'
		),
		5 => array(
			'btn', 'sb', 'bb', 'mp', 'co'
		),
		6 => array(
			'btn', 'sb', 'bb', 'utg', 'mp', 'co'
		),
		7 => array(
			'btn', 'sb', 'bb', 'utg', 'utg2', 'mp', 'co'
		),
		8 => array(
			'btn', 'sb', 'bb', 'utg', 'utg2', 'mp', 'mp2', 'co'
		),
		9 => array(
			'btn', 'sb', 'bb', 'utg', 'utg2', 'utg3', 'mp', 'mp2', 'co'
		),
		10 => array(
			'btn', 'sb', 'bb', 'utg', 'utg2', 'utg3', 'mp', 'mp2', 'mp3', 'co'
		)
	);

	public static function getPositions($nbPlayers)
	{
		if (isset(self::$positions[$nbPlayers]))
			return self::$positions[$nbPlayers];
			
		return array();
	}
	
	public static function getPositionsOrderByBtnPos($nbPlayers, $btnPos)
	{
		$positions = self::getPositions($nbPlayers);
		
		if ($btnPos > $nbPlayers)
			throw new Exception("Invalid button position : {$btnPos} > {$nbPlayers}");
			
		for ($i=1; $i<$btnPos; $i++) {
			$pos = array_pop($positions);
			array_unshift($positions, $pos);
		}
		
		return $positions;
	}
}