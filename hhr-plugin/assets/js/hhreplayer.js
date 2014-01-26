String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
};
$ = jQuery;
/*************** COMMANDS *************/
var commands = {};

commands.Raise = function Raise(replayer, player, data) {
  this.replayer = replayer;
  this.player = player;
  this.amount = data.amount;
  
  this.apply = function() {
    stack = parseFloat(this.player.getStack()) - parseFloat(this.amount) + parseFloat(this.player.getBet());
    this.player.setStack(stack);
    realAmount = parseFloat(this.amount) - parseFloat(this.player.getBet());  
    this.player.setBet(this.amount);
    this.player.pushBet(this.amount);
    this.player.setAction('raises'); 
    
	  this.replayer.pot.add(realAmount);
    this.replayer.pot.pushRaise(this.player.getBet());
  };
  
  this.undo = function() {
    lastBet = this.player.getLastBet();
    oldAmount = parseFloat(this.player.getBet()) - parseFloat(this.amount) + lastBet;
    this.player.setBet(oldAmount);
	  stack = parseFloat(this.player.getStack()) + parseFloat(this.amount) - lastBet;
	  this.player.setStack(stack);
    this.player.setAction('');
    
    this.replayer.pot.remove(this.amount - lastBet);
    this.replayer.pot.popRaise();
  };
};

commands.Call = function Call(replayer, player, data) {
  this.replayer = replayer;
  this.player = player;
  this.amount = data.amount;
  
  this.apply = function() {
	  stack = parseFloat(this.player.getStack()) - parseFloat(this.amount);
    this.player.setStack(stack);
    amount = parseFloat(this.amount) + parseFloat(this.player.getBet());
    this.player.setBet(amount);
    this.player.pushBet(amount);
    this.player.setAction('calls');
     
    this.replayer.pot.add(this.amount); 
  };
  
  this.undo = function() {
    this.player.popBet();
    oldAmount = parseFloat(this.player.getBet()) - parseFloat(this.amount);
    this.player.setBet(oldAmount);
	  stack = parseFloat(this.player.getStack()) + parseFloat(this.amount);
	  this.player.setStack(stack);
    this.player.setAction('');
    
    this.replayer.pot.remove(this.amount);
  };
};

commands.Fold = function Fold(replayer, player, data) {
  this.replayer = replayer;
  this.player = player;
  
  this.apply = function(){
    this.player.setAction('folds');
    this.player.muck();
  };
  
  this.undo = function(){
    this.player.setAction('');
    this.player.unMuck();
  };
};

commands.Check = function Check(replayer, player, data) {
  this.replayer = replayer;
  this.player = player;
  
  this.apply = function(){
    this.player.setAction('checks');
    this.player.pushBet(0);
  };
  
  this.undo = function(){
    this.player.setAction('');
  };
};

commands.Street = function Street(replayer, player, data) {
  this.replayer = replayer;
  this.frame = player;
  this.cards = data.cards;
  
  this.apply = function(){
	  for (k in action.cards) {
      card = action.cards[k]; 
      this.frame.append('<span class="card card-'+card+'"></span>');
    }
    this.frame.addClass('show');
    this.replayer.savePlayersStates(this.frame);
    replayer.pot.flush();
    this.replayer.pot.pushRaise(0);
  };
  
  this.undo = function(){
    this.frame.html('');
	  this.frame.removeClass('show');
    this.replayer.reloadPlayersStates(this.frame);
    this.replayer.pot.popRaise();
  };
};

commands.Show = function Show(replayer, player, data) {
  this.replayer = replayer;
  this.players = data.players;
  this.winner = data.winner;
  
  this.apply = function(){
    for (k in this.players) {
      cards = this.players[k];
      player = this.replayer.createPlayer(cards.player, this.replayer.currency);
      player.showCards(cards.hc);
    }
    this.replayer.pot.pushRaise(0);
  }
  
  this.undo = function(){
    for (k in this.players) {
      cards = this.players[k];
      player = this.replayer.createPlayer(cards.player, this.replayer.currency);
      player.hideCards(cards.hc);
    }
    this.replayer.pot.popRaise();
  }
};

commands.Winning = function Winning(replayer, player, data) {
  this.replayer = replayer;
  this.players = data.players;
  this.frame = $(replayer.table);
  
  this.apply = function(){
    this.replayer.savePlayersStates(this.frame);

    for (k in this.players) {
      winning = this.players[k];
      player = this.replayer.createPlayer(winning.player, this.replayer.currency);
      newStack = parseFloat(player.getStack()) + parseFloat(winning.amount);
      player.setStack(newStack);
      player.setBet(winning.amount);
      this.replayer.pot.remove(winning.amount);
    }
    this.replayer.pot.flush();
    this.replayer.pot.total = 0;
  };
  
  this.undo = function(){
    this.replayer.reloadPlayersStates(this.frame);
  };
};
  

/*************** PLAYER *************/
function Player(frame, currency) {
  this.frame = frame;
  this.currency = currency;
}

Player.prototype.pushBet = function(amount) {
  dataBets = this.frame.find('.bet').attr('data-bets');
  
  if ('undefined' == typeof dataBets) {
    bets = [amount];
  } else {
    bets = JSON.parse(dataBets);
    bets.push(amount);
  }
  
  this.frame.find('.bet').attr('data-bets', JSON.stringify(bets));
}

Player.prototype.popBet = function() {
  dataBets = this.frame.find('.bet').attr('data-bets');
  
  if ('undefined' == typeof dataBets) {
    bets = [];
  } else {
    bets = JSON.parse(dataBets);
    bets.pop();
  }
  
  this.frame.find('.bet').attr('data-bets', JSON.stringify(bets));
}

Player.prototype.getLastBet = function() {
  this.popBet();
  dataBets = this.frame.find('.bet').attr('data-bets');
  
  if ('undefined' == typeof dataBets)
    return 0;
  
  bets = JSON.parse(dataBets);
  if (bets.length === 0)
    return 0;
  
  return parseFloat(bets[bets.length-1]);
}

Player.prototype.getBet = function() {
  amount = this.frame.find('.bet').attr('data-amount');
  if ('undefined' == typeof amount)
    return 0;
  
  return this.frame.find('.bet').attr('data-amount');
}

Player.prototype.setBet = function(amount) {
  if ('string' === typeof amount)
    amount = parseFloat(amount);
  
  this.frame.find('.bet').attr('data-amount', amount.toFixed(2));
  
  if (amount > 0) {
    this.frame.find('.bet').html(amount.toFixed(2)+this.currency);
  } else {
    this.frame.find('.bet').html('');
  }
};

Player.prototype.getStack = function() {
  amount = this.frame.find('.stack').attr('data-amount');
  if ('undefined' == typeof amount)
    return 0;
  
  return this.frame.find('.stack').attr('data-amount');
}

Player.prototype.setStack = function(amount) {
  if ('string' === typeof amount)
    amount = parseFloat(amount);
  
  stack = this.frame.find('.stack');
  stack.attr('data-amount', amount.toFixed(2));
  
  if (amount > 0) {
    stack.removeClass('all-in');
    stack.html(amount.toFixed(2)+this.currency);
  } else {
    stack.addClass('all-in');
    stack.html('ALL IN');
  }
};

Player.prototype.setAction = function(action) {
  this.frame.find('.action').html(action);
}

Player.prototype.muck = function() {
  this.frame.find('.cards').addClass('mucked');
}

Player.prototype.unMuck = function() {
  this.frame.find('.cards').removeClass('mucked');
}

Player.prototype.showCards = function(cards) {
  for (k in cards) {
    card = this.frame.find('.cards > .card:eq('+k+')')
    card.removeClass('card-cover').addClass('card-'+cards[k]);
  }
}

Player.prototype.hideCards = function(cards) {
  for (k in cards) {
    card = this.frame.find('.cards > .card:eq('+k+')')
    card.removeClass('card-'+cards[k]).addClass('card-cover');
  }
}


/*************** REPLAYER *************/
function HHReplayer(options) {
  this.table = options.table;
  this.currency = '';
  this.playBtn = options.playBtn;
  this.data = undefined;
  this.pot = {
    pot:options.pot,
    current:0.0,
    total:0.0,
    raises:[],
    
    add:function(v){
      if ('string' === typeof v)
        v = parseFloat(v);
     
      this.total += v
      this.total = Math.round(this.total * 100)/100;
    },
    remove:function(v){
      if ('string' === typeof v)
        v = parseFloat(v);
     
      this.total -= v
      this.total = Math.round(this.total * 100)/100;
    },
    flush:function(){
      this.current = this.total;
    },
    draw:function(){
      $(this.pot).find('.current > span').html(this.current.toFixed(2));
      $(this.pot).find('.total > span').html(this.total.toFixed(2));
    },
    reset:function(){
      this.current = 0.0;
      this.total = 0.0;
    },
    
    pushRaise:function(v){
      this.raises.push(this.toFloat(v));
    },
    popRaise:function(){
      this.raises.pop();
    },
    getOdds:function(player){
      if (this.raises.length == 0 || this.raises[this.raises.length-1] <= 0)
        return 'N/A';
      
	  playerMoneyInPot = player.getBet();
	  
      toCall = this.raises[this.raises.length-1] - playerMoneyInPot;
	  
	  if (toCall > player.getStack()) {
        toCall = parseFloat(player.getStack());
      }
	  
      odds = this.total / toCall;
      percentage = 1/(odds+1)*100;
      return odds.toFixed(2) + ':1 ('+percentage.toFixed(1)+'%)';
    },
    
    toFloat:function(v){
      if ('string' === typeof v)
        return parseFloat(v);
      
      return v;
    }
  };
};


HHReplayer.prototype.createPlayer = function(frameClass) {
  if ('flop' === frameClass || 'turn' === frameClass || 'river' === frameClass)
    return $(this.table).find('.'+frameClass);
  
  return new Player($(this.table).find('.'+frameClass), this.currency);
};

HHReplayer.prototype.createCommand = function(data) {
  player = this.createPlayer(data.player);
  return new commands[data.type.capitalize()](this, player, data);
};

HHReplayer.prototype.savePlayersStates = function(frame) {
  states = {
    players: [],
    pot: {}
  };
  // save the state of the players
  $(this.table).find('.player').each(function(){
    playerState = {
      frame: $(this).find('.name').html(),
      bet: $(this).find('.bet').html(),
      betData: $(this).find('.bet').attr('data-amount'),
      stack: $(this).find('.stack').attr('data-amount')
    };
    states.players.push(playerState);
  });
  
  // save the state of the pot
  states.pot.current = this.pot.current;
  states.pot.total = this.pot.total;
  
  frame.attr('data-state', JSON.stringify(states));
  $(this.table).find('.player > .bet').attr('data-amount', 0).html(''); 
};

HHReplayer.prototype.reloadPlayersStates = function(frame) {
  states = JSON.parse(frame.attr('data-state'));
  for (k in states.players) {
    state = states.players[k];
    playerFrame = $(this.table).find('.'+state.frame);
    playerFrame.find('.bet').html(state.bet).attr('data-amount', state.betData);
    player = new Player(playerFrame, this.currency);
    player.setStack(state.stack);
  }
  
  // reload pot
  this.pot.current = states.pot.current;
  this.pot.total = states.pot.total;
};

HHReplayer.prototype.draw = function() {
  this.drawOdds();
  this.pot.draw();
};

HHReplayer.prototype.drawOdds = function() {
  step = parseInt($(this.table).attr('data-step'));
  step++;
  
  if (step >= this.data.actions.length)
    return $('#pot-odds').html('N/A');
  
  action = this.data.actions[step];
  player = this.createPlayer(action.player);
  //console.log(player);
  if (!(player instanceof Player) || action.type == 'show')
    return $('#pot-odds').html('N/A');
    
  $('#pot-odds').html(this.pot.getOdds(player)); 
};

HHReplayer.prototype.initQuickAccessBtns = function() {
  streetBtns = $('#action-bar .streets');
  streetBtns.find('a:not(:first)').addClass('disabled');
  
  for (step in this.data.actions) {
    action = this.data.actions[step];
    if ('street' === action.type) {
      switch (action.player) {
        case 'flop':
          streetBtns.find('.btn-flop')
            .removeClass('disabled')
            .attr('data-goto-step', step);
          break;
        case 'turn':
          streetBtns.find('.btn-turn')
            .removeClass('disabled')
            .attr('data-goto-step', step);
          break;
        case 'river':
          streetBtns.find('.btn-river')
            .removeClass('disabled')
            .attr('data-goto-step', step);
          break;
      }
    }
  }

  var _this = this;
  streetBtns.on('click', 'a:not(.disabled, .inactive)', function(e){
	e.preventDefault();
    $(this).parents('.streets').find('a').addClass('inactive');
    _this.gotoStep($(this).attr('data-goto-step'));
    $(this).parents('.streets').find('a').removeClass('inactive');
  });
}

HHReplayer.prototype.init = function(data) {
    this.data = data;
	this.currency = data.currency;
    $(this.table).addClass('max'+data.format);
    //reset table
    $(this.table).attr('data-step', '-1');
    $(this.table).find('.player').remove();
    $(this.table).find('.flop').html('');
    $(this.table).find('.turn').html('');
    $(this.table).find('.river').html('');
    this.pot.reset();
  
    for (i in data.stacks) {
      stack = data.stacks[i];
      player = $('.template').clone();
      player.find('.name').html(stack.name);
      player.find('.stack').html(stack.stack+this.currency).attr('data-amount', stack.stack);
      player.removeClass('template').addClass(stack.name);
      if (stack.pos != stack.name)
        player.addClass(stack.pos);
      
      // show cards
      if (stack.pos == data.hero) {
        for (k in stack.hc) {
          player.find('.cards > .card:eq('+k+')').addClass('card-'+stack.hc[k]);  
        }
      } else {
        player.find('.cards > .card').addClass('card-cover');
      }
      player.appendTo(this.table);
      
      if (stack.pos === 'sb' || stack.pos === 'bb') { 
        action = {type:'raise', player:stack.name, amount:data.blinds[stack.pos]};
        command = this.createCommand(action); 
        command.apply(); 
      }
    }
    // remove raises from blinds
    $(this.table).find('.player > .action').html('');
  
    for (playerName in data.antes) {
      ante = data.antes[playerName];
      player = this.createPlayer(playerName);
      player.setStack(player.getStack() - ante);
      this.pot.add(ante);
    }
  
    for (i in data.bigBlinds) {
      playerName = data.bigBlinds[i];
      action = {type:'raise', player:playerName, amount:data.blinds['bb']};
      command = this.createCommand(action);
      command.apply(); 
    }
    
	this.initQuickAccessBtns();
	
    this.draw();
};

HHReplayer.prototype.next = function() {
  step = parseInt($(this.table).attr('data-step'));
  step++;
  if (step >= this.data.actions.length) return false;
    
  $(this.table).attr('data-step', step);
  $(this.table).find('.player > .action').html('');
  
  action = this.data.actions[step];
  command = this.createCommand(action); 
  command.apply();
  
  this.draw();
  return true;
};

HHReplayer.prototype.prev = function() {
  step = parseInt($(this.table).attr('data-step'));
  if (step < 0) return false;
     
  action = this.data.actions[step];
  command = this.createCommand(action);
  command.undo();
  
  if (step > 0) {
    action = this.data.actions[step-1];
    if ('street' !== action.type) {
      command = this.createCommand(action);
      command.undo();
      command.apply();
    }
  }
  
  step--;
  $(this.table).attr('data-step', step);
  
  this.draw();
  return true;
};

HHReplayer.prototype.play = function() {
  if ('true' === $(this.playBtn).attr('data-active')) {
    result = this.next();
    
    if (!result) {
      $(this.playBtn).click();
      return;
    }
    
    var _this = this;
    setTimeout(function() { _this.play(); }, 1200);
  }
};

HHReplayer.prototype.gotoStep = function(targetStep) {
  step = parseInt($(this.table).attr('data-step'));
  if (step == targetStep) return;
  
  if (step < targetStep) {
    for (i=step; i<targetStep; i++) {
      this.next();
    }
  } else {
    for (i=step; i>targetStep; i--) {
      this.prev();
    }
  }
};


