<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class SimpleInvMenuTransaction implements InvMenuTransaction{

	public function __construct(
		readonly private Player $player,
		readonly private Item $out,
		readonly private Item $in,
		readonly private SlotChangeAction $action,
		readonly private InventoryTransaction $transaction
	){}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getOut() : Item{
		return $this->out;
	}

	public function getIn() : Item{
		return $this->in;
	}

	public function getItemClicked() : Item{
		return $this->getOut();
	}

	public function getItemClickedWith() : Item{
		return $this->getIn();
	}

	public function getAction() : SlotChangeAction{
		return $this->action;
	}

	public function getTransaction() : InventoryTransaction{
		return $this->transaction;
	}

	public function continue() : InvMenuTransactionResult{
		return new InvMenuTransactionResult(false);
	}

	public function discard() : InvMenuTransactionResult{
		return new InvMenuTransactionResult(true);
	}
}