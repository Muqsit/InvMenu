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

namespace muqsit\invmenu;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\Player;

class InvMenu implements MenuIds{

	public static function create(string $inventory_class, ...$args) : InvMenu{
		return new InvMenu($inventory_class, ...$args);
	}

	/** @var bool */
	private $readonly = false;

	/** @var string|null */
	private $name;

	/** @var callable|null */
	private $listener;

	/** @var callable|null */
	private $inventory_close_listener;

	/** @var bool */
	private $sessionized = false;

	/** @var BaseFakeInventory[]|null */
	private $sessions;

	/** @var BaseFakeInventory|null */
	private $inventory;

	public function __construct(string $inventory_class, ...$args){
		if(!is_subclass_of($inventory_class, BaseFakeInventory::class, true)){
			throw new \InvalidArgumentException($inventory_class . " must extend " . BaseFakeInventory::class . ".");
		}

		$this->inventory = new $inventory_class($this, ...$args);
	}

	public function getInventory(?Player $player = null, ?string $custom_name = null) : BaseFakeInventory{
		if($this->sessionized){
			if($player === null){
				throw new \InvalidArgumentException("You need to specify a " . Player::class . " instance as the first parameter of getInventory() while fetching an inventory from a sessionized InvMenu instance.");
			}

			return $this->sessions[$uuid = $player->getId()] ?? ($this->sessions[$uuid] = $this->inventory->createNewInstance($this));
		}

		return $this->inventory;
	}

	public function readonly(bool $value = true) : InvMenu{
		$this->readonly = $value;
		return $this;
	}

	public function isReadonly() : bool{
		return $this->readonly;
	}

	public function setName(?string $name) : InvMenu{
		$this->name = $name;
		return $this;
	}

	public function sessionize(bool $value = true) : InvMenu{
		if($this->sessionized !== $value){
			$this->clearSessions();
			$this->sessionized = $value;
		}

		return $this;
	}

	public function getListener() : ?callable{
		return $this->listener;
	}

	public function setListener(?callable $listener) : InvMenu{
		$this->listener = $listener;
		return $this;
	}

	public function getInventoryCloseListener() : ?callable{
		return $this->inventory_close_listener;
	}

	public function setInventoryCloseListener(?callable $inventory_close_listener) : InvMenu{
		$this->inventory_close_listener = $inventory_close_listener;
		return $this;
	}

	public function send(Player $player, ?string $custom_name = null) : bool{
		return $this->getInventory($player)->send($player, $custom_name ?? $this->name);
	}

	public function clearSessions(bool $remove_windows = true) : void{
		if($this->sessionized){
			$inventories = $this->sessions;
			$this->sessions = [];
		}else{
			$inventories = [$this->getInventory()];
		}

		if($remove_windows){
			foreach($inventories as $inventory){
				foreach($inventory->getViewers() as $player){
					$player->removeWindow($inventory);
				}
			}
		}
	}

	public function onInventoryClose(Player $player) : void{
		if($this->sessionized){
			unset($this->sessions[$player->getId()]);
		}
	}

	public function __clone(){
		$this->inventory = $this->inventory->createNewInstance($this);
		$this->clearSessions(false);
	}
}
