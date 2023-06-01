<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use Closure;
use LogicException;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class DeterministicInvMenuTransaction implements InvMenuTransaction{

	public function __construct(
		readonly private InvMenuTransaction $inner,
		readonly private InvMenuTransactionResult $result
	){}

	public function continue() : InvMenuTransactionResult{
		throw new LogicException("Cannot change state of deterministic transactions");
	}

	public function discard() : InvMenuTransactionResult{
		throw new LogicException("Cannot change state of deterministic transactions");
	}

	public function then(?Closure $callback) : void{
		$this->result->then($callback);
	}

	public function getPlayer() : Player{
		return $this->inner->getPlayer();
	}

	public function getOut() : Item{
		return $this->inner->getOut();
	}

	public function getIn() : Item{
		return $this->inner->getIn();
	}

	public function getItemClicked() : Item{
		return $this->inner->getItemClicked();
	}

	public function getItemClickedWith() : Item{
		return $this->inner->getItemClickedWith();
	}

	public function getAction() : SlotChangeAction{
		return $this->inner->getAction();
	}

	public function getTransaction() : InventoryTransaction{
		return $this->inner->getTransaction();
	}
}