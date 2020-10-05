<?php

/*
 *  ___            __  __
 * |_ _|_ ____   _|  \/  | ___ _ __  _   _
 *  | || '_ \ \ / / |\/| |/ _ \ '_ \| | | |
 *  | || | | \ V /| |  | |  __/ | | | |_| |
 * |___|_| |_|\_/ |_|  |_|\___|_| |_|\__,_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Muqsit
 * @link http://github.com/Muqsit
 *
*/

declare(strict_types=1);

namespace muqsit\invmenu;

use Closure;
use InvalidStateException;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\metadata\MenuMetadata;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;

class InvMenu implements MenuIds{

	public static function create(string $identifier) : InvMenu{
		return new InvMenu(InvMenuHandler::getMenuType($identifier));
	}

	/**
	 * @param Closure|null $listener
	 * @return Closure
	 *
	 * @phpstan-param Closure(DeterministicInvMenuTransaction) : void $listener
	 */
	public static function readonly(?Closure $listener = null) : Closure{
		return static function(InvMenuTransaction $transaction) use($listener) : InvMenuTransactionResult{
			$result = $transaction->discard();
			if($listener !== null){
				$listener(new DeterministicInvMenuTransaction($transaction, $result));
			}
			return $result;
		};
	}

	/** @var MenuMetadata */
	protected $type;

	/** @var string|null */
	protected $name;

	/** @var Closure|null */
	protected $listener;

	/** @var Closure|null */
	protected $inventory_close_listener;

	/** @var InvMenuInventory */
	protected $inventory;

	public function __construct(MenuMetadata $type){
		if(!InvMenuHandler::isRegistered()){
			throw new InvalidStateException("Tried creating menu before calling " . InvMenuHandler::class . "::register()");
		}
		$this->type = $type;
		$this->inventory = $this->type->createInventory();
	}

	public function getType() : MenuMetadata{
		return $this->type;
	}

	public function getName() : ?string{
		return $this->name;
	}

	public function setName(?string $name) : self{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param Closure|null $listener
	 * @return self
	 *
	 * @phpstan-param Closure(InvMenuTransaction) : InvMenuTransactionResult $listener
	 */
	public function setListener(?Closure $listener) : self{
		$this->listener = $listener;
		return $this;
	}

	/**
	 * @param Closure|null $listener
	 * @return self
	 *
	 * @phpstan-param Closure(Player, \pocketmine\inventory\Inventory) : void $listener
	 */
	public function setInventoryCloseListener(?Closure $listener) : self{
		$this->inventory_close_listener = $listener;
		return $this;
	}

	/**
	 * @param Player $player
	 * @param string|null $name
	 * @param Closure|null $callback
	 *
	 * @phpstan-param Closure(bool) : void $callback
	 */
	final public function send(Player $player, ?string $name = null, ?Closure $callback = null) : void{
		$session = PlayerManager::getNonNullable($player);
		$network = $session->getNetwork();
		$network->dropPending();

		$session->removeWindow();

		$network->waitUntil($network->getGraphicWaitDuration(), function(bool $success) use($player, $session, $name, $callback) : void{
			if($success){
				$extra_data = $session->getMenuExtradata();
				$extra_data->setName($name ?? $this->getName());
				$extra_data->setPosition($this->type->calculateGraphicPosition($player));
				if($this->type->sendGraphic($player, $extra_data)){
					$session->setCurrentMenu($this, $callback);
				}else{
					$extra_data->reset();
					if($callback !== null){
						$callback(false);
					}
				}
			}elseif($callback !== null){
				$callback(false);
			}
		});
	}

	public function getInventory() : InvMenuInventory{
		return $this->inventory;
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function sendInventory(Player $player) : bool{
		return $player->addWindow($this->getInventory()) !== -1;
	}

	public function handleInventoryTransaction(Player $player, Item $out, Item $in, SlotChangeAction $action, InventoryTransaction $transaction) : InvMenuTransactionResult{
		$inv_menu_txn = new InvMenuTransaction($player, $out, $in, $action, $transaction);
		return $this->listener !== null ? ($this->listener)($inv_menu_txn) : $inv_menu_txn->continue();
	}

	public function onClose(Player $player) : void{
		if($this->inventory_close_listener !== null){
			($this->inventory_close_listener)($player, $this->getInventory());
		}

		PlayerManager::getNonNullable($player)->removeCurrentMenu();
	}
}
