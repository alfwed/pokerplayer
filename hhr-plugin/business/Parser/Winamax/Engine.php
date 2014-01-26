<?php

class Parser_Winamax_Engine extends Parser_Abstract
{
	const T_HEADER = '@Winamax Poker - .+ - .+ - [^\(]+\((\d.+/)?(\d.+)/(\d.+)\) - .*@';
	const T_TABLE = '@Table: .* (\d+)-max .* Seat #(\d+) .*@';
	const T_STACK = '@Seat \d+: (.+) \((\d.+)\)@';
	const T_ANTE = '@(.+) posts ante (\d.+)@';
	const T_BIG_BLIND = '@(.+) posts big blind \d.+ out of position@';
	const T_DEALT = '@Dealt to (.+) \[(..) (..)\]@';

	const T_FOLD = '@(.+) folds@';
	const T_RAISE = '@(.+) raises \S+ to (\S+).*@';
	const T_BET = '@(.+) bets (\S+)@';
	const T_CALL = '@(.+) calls (\S+)@';
	const T_CHECK = '@(.+) checks@';
	
	const T_FLOP = '@\*\*\* FLOP \*\*\* \[(..) (..) (..)\]@';
	const T_TURN = '@\*\*\* TURN \*\*\* \[.. .. ..\]\[(..)\]@';
	const T_RIVER = '@\*\*\* RIVER \*\*\* \[.. .. .. ..\]\[(..)\]@';
	
	const T_SHOWDOWN_WON = '@Seat \d+: ([^\(]+)( \(.+\))? showed \[(..) (..)\] and won (\d\S+)@';
	const T_SHOWDOWN_LOST = '@Seat \d+: ([^\(]+)( \(.+\))? showed \[(..) (..)\]@';
	const T_WINNER = '@Seat \d+: (.+) won (\d\S+)@';
	
	protected $rules = array(
		'parseHeader' => self::T_HEADER,
		'parseTable' => self::T_TABLE,
		'parseStack' => self::T_STACK,
		'parseAnte' => self::T_ANTE,
		'parseBigBlind' => self::T_BIG_BLIND,
		'parseDealt' => self::T_DEALT,
		'parseFold' => self::T_FOLD,
		'parseRaise' => self::T_RAISE,
		'parseBet' => self::T_BET,
		'parseCall' => self::T_CALL,
		'parseCheck' => self::T_CHECK,
		'parseFlop' => self::T_FLOP,
		'parseTurn' => self::T_TURN,
		'parseRiver' => self::T_RIVER,
		'parseShowdownWon' => self::T_SHOWDOWN_WON,
		'parseShowdownLost' => self::T_SHOWDOWN_LOST,
		'parseWinner' => self::T_WINNER,
	);

	private $btnPosition;
	private $hero;
	private $showdown = array();
	private $winnings = array();
	
	public function isLogValid()
	{
		if (preg_match(self::T_HEADER, $this->file[0], $matches)) 
			return true;
			
		return false;
	}
	
	protected function postParsing()
	{
		$this->detectAwayPlayers();
		
		$nb = count($this->hand->stacks);
		$positions = TablePosition::getPositionsOrderByBtnPos($nb, $this->btnPosition);
		
		foreach ($this->hand->stacks as $k => &$stack) {
			$stack->pos = $positions[$k];
			
			if (isset($stack->hc))
				$this->hand->heroPosition = $stack->pos;
		}
		
		if (!empty($this->showdown)) {
			$action = new stdClass();
			$action->type = 'show';
			$action->players = $this->showdown;
			$this->hand->actions[] = $action;
		}
		
		if (!empty($this->winnings)) {
			$action = new stdClass();
			$action->type = 'winning';
			$action->players = $this->winnings;
			$this->hand->actions[] = $action;
		}
	}
	
	private function detectAwayPlayers()
	{
		$actives = array();
		foreach ($this->hand->actions as $action) {
			if (isset($action->player)) 
				$actives[$action->player] = true;
		}
		
		foreach ($this->hand->stacks as $k => $stack) {
			if (!isset($actives[$stack->name])) {
				unset($this->hand->stacks[$k]);
			}
		}
		$this->hand->stacks = array_values($this->hand->stacks);
	}
	
	protected function parseHeader($line, $matches)
	{
		$this->detectCurrency($matches[2]);
		$this->hand->blinds['sb'] = $this->rmCurrency($matches[2]);
		$this->hand->blinds['bb'] = $this->rmCurrency($matches[3]);
	}
	
	protected function parseTable($line, $matches)
	{
		$this->hand->format = $matches[1];
		$this->btnPosition = $matches[2];
	}
	
	protected function parseStack($line, $matches)
	{
		$stack = new stdClass();
		$stack->name = $matches[1];
		$stack->stack = $this->rmCurrency($matches[2]);
		$this->hand->addStack($stack);
	}
	
	protected function parseAnte($line, $matches)
	{
		$this->hand->addAnte($matches[1], $matches[2]);
	}
	
	protected function parseBigBlind($line, $matches)
	{
		$this->hand->addBigBlinds($matches[1]);
	}
	
	protected function parseDealt($line, $matches)
	{
		$cards = array(strtoupper($matches[2]), strtoupper($matches[3]));
		$this->hero = $this->hand->cleanPlayerName($matches[1]);
		
		foreach ($this->hand->stacks as &$stack) {
			if ($stack->name === $this->hero) {
				$stack->hc = $cards;
				break;
			}
		}
	}
	
	protected function parseFold($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'fold';
		$action->player = $matches[1];
		$this->hand->addAction($action);
	}
	
	protected function parseRaise($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'raise';
		$action->player = $matches[1];
		$action->amount = $this->rmCurrency($matches[2]);
		$this->hand->addAction($action);
	}
	
	protected function parseBet($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'raise';
		$action->player = $matches[1];
		$action->amount = $this->rmCurrency($matches[2]);
		$this->hand->addAction($action);
	}
	
	protected function parseCall($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'call';
		$action->player = $matches[1];
		$action->amount = $this->rmCurrency($matches[2]);
		$this->hand->addAction($action);
	}
	
	protected function parseCheck($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'check';
		$action->player = $matches[1];
		$this->hand->addAction($action);
	}
	
	protected function parseFlop($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'street';
		$action->player = 'flop';
		$action->cards = array(
			strtoupper($matches[1]),
			strtoupper($matches[2]),
			strtoupper($matches[3])
		);
		$this->hand->addAction($action);
	}
	
	protected function parseTurn($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'street';
		$action->player = 'turn';
		$action->cards = array(
			strtoupper($matches[1]),
		);
		$this->hand->addAction($action);
	}
	
	protected function parseRiver($line, $matches)
	{
		$action = new stdClass();
		$action->type = 'street';
		$action->player = 'river';
		$action->cards = array(
			strtoupper($matches[1]),
		);
		$this->hand->addAction($action);
	}
	
	protected function parseShowdownWon($line, $matches)
	{
		$player = $this->hand->cleanPlayerName($matches[1]);
		$this->addWinnings($player, $matches[5]);
	
		if ($player === $this->hero)
			return;
		
		$sd = new stdClass();
		$sd->player = $player;
		$sd->hc = array(
			strtoupper($matches[3]), 
			strtoupper($matches[4])
		);
		$this->showdown[] = $sd;
	}
	
	protected function parseShowdownLost($line, $matches)
	{
		$player = $this->hand->cleanPlayerName($matches[1]);
		if ($player === $this->hero)
			return;
		
		$sd = new stdClass();
		$sd->player = $player;
		$sd->hc = array(
			strtoupper($matches[3]), 
			strtoupper($matches[4])
		);
		$this->showdown[] = $sd;
	}
	
	protected function parseWinner($line, $matches)
	{
		$this->addWinnings($matches[1], $matches[2]);
	}
	
	private function addWinnings($player, $amount)
	{
		$winning = new stdClass();
		$winning->player = $player;
		$winning->amount = $this->rmCurrency($amount);
		$this->winnings[] = $winning;
	}
	
}



