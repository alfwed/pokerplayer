<?php

require_once dirname(__FILE__).'/../Hand.php';
require_once dirname(__FILE__).'/../TablePosition.php';
require_once 'Abstract.php';
require_once 'Winamax/Engine.php';

class Parser_Factory
{
	private static $availableParsers = array(
		'winamax',
	);

	public static function getParser($lines)
	{
		foreach (self::$availableParsers as $room) {
			$parser = self::$room($lines);
			if ($parser->isLogValid())
				return $parser;
		}
		
		return null;
	}

	public static function winamax($lines)
	{
		return new Parser_Winamax_Engine(new Hand(), $lines);
	}

}