<?php

class Hand
{
	public $currency;
	public $limit;
	public $blinds = array();
	public $antes = array();
	public $bigBlinds = array();
	public $format;
	public $heroPosition;
	public $stacks = array();
	public $actions = array();
	
	public function toJSON()
	{
		$o = new stdClass();
		$o->currency = $this->currency;
		$o->limit = $this->limit;
		$o->blinds = $this->blinds;
		$o->antes = $this->antes;
		$o->bigBlinds = $this->bigBlinds;
		$o->format = $this->format;
		$o->hero = $this->heroPosition;
		$o->stacks = $this->stacks;
		$o->actions = $this->actions;
		
		return json_encode($o);
	}
	
	public function isValid()
	{
		return !is_null($this->currency)
			&& count($this->blinds) === 2
			&& !is_null($this->format)
			&& !is_null($this->heroPosition)
			&& !is_null($this->stacks)
			&& !is_null($this->actions);
	}
	
	public function addStack($stack)
	{
		$stack->name = $this->cleanPlayerName($stack->name);
		$this->stacks[] = $stack;
	}
	
	public function addAnte($player, $ante)
	{
		$this->antes[$this->cleanPlayerName($player)] = $ante;
	}
	
	public function addBigBlinds($player)
	{
		$this->bigBlinds[] = $this->cleanPlayerName($player);
	}
	
	public function addAction($action)
	{
		$action->player = $this->cleanPlayerName($action->player);
		$this->actions[] = $action;
	}
	
	public function cleanPlayerName($name)
	{
		$invalidChars = array(' ', '.' , '#', '"', "'");
		return str_replace($invalidChars, '', $name);
	}
}