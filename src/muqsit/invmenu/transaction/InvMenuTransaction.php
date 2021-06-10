<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface InvMenuTransaction{

	public function getPlayer() : Player;

	public function getOut() : Item;

	public function getIn() : Item;

	/**
	 * Returns the item that was clicked / taken out of the inventory.
	 *
	 * @link InvMenuTransaction::getOut()
	 * @return Item
	 */
	public function getItemClicked() : Item;

	/**
	 * Returns the item that an item was clicked with / placed in the inventory.
	 *
	 * @link InvMenuTransaction::getIn()
	 * @return Item
	 */
	public function getItemClickedWith() : Item;

	public function getAction() : SlotChangeAction;

	public function getTransaction() : InventoryTransaction;

	public function continue() : InvMenuTransactionResult;

	public function discard() : InvMenuTransactionResult;
}