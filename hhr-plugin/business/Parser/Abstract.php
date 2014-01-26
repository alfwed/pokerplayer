<?php

abstract class Parser_Abstract
{
	protected $hand;
	protected $file;
	
	protected $availableCurrencies = array('€', '$', '£');
	protected $currency;
	
	protected $rules = array();

	
	public function __construct($hand, $file)
	{
		$this->hand = $hand;
		$this->file = $file;
	}
	
	abstract public function isLogValid();
	
	public function parse()
	{
		foreach ($this->file as $line) {
			$this->parseLine($line);
		}
		
		$this->postParsing();
		
		return $this->hand;
	}
	
	protected function parseLine($line)
	{
		foreach ($this->rules as $method => $rule) {
			if (preg_match($rule, $line, $matches)) {
				$this->$method($line, $matches);
				break;
			}
		}
	}
	
	abstract protected function postParsing();
	
	protected function rmCurrency($value)
	{
		return str_replace($this->currency, '', $value);
	}
	
	protected function detectCurrency($value)
	{
		foreach ($this->availableCurrencies as $currency) {
			if (false !== strpos($value, $currency)) {
				$this->currency = $currency;
				$this->hand->currency = $currency;
				return;
			}
		}
		
		//throw new Exception('Unknown currency');
		$this->currency = '';
		$this->hand->currency = '';
	}
}
