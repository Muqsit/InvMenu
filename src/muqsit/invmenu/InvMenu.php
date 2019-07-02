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

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\metadata\MenuMetadata;
use muqsit\invmenu\session\MenuExtradata;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class InvMenu implements MenuIds{

	public const INVENTORY_HEIGHT = 3;

	public static function create(string $identifier, ...$args) : SharedInvMenu{
		return new SharedInvMenu(InvMenuHandler::getMenuType($identifier), ...$args);
	}

	public static function createSessionized(string $identifier) : SessionizedInvMenu{
		return new SessionizedInvMenu(InvMenuHandler::getMenuType($identifier));
	}

	/** @var MenuMetadata */
	protected $type;

	/** @var bool */
	protected $readonly = false;

	/** @var string|null */
	protected $name;

	/** @var callable|null */
	protected $listener;

	/** @var callable|null */
	protected $inventory_close_listener;

	public function __construct(MenuMetadata $type){
		$this->type = $type;
	}

	public function getType() : MenuMetadata{
		return $this->type;
	}

	public function getName() : ?string{
		return $this->name;
	}

	public function setName(?string $name) : InvMenu{
		$this->name = $name;
		return $this;
	}

	public function isReadonly() : bool{
		return $this->readonly;
	}

	public function readonly(bool $value = true) : InvMenu{
		$this->readonly = $value;
		return $this;
	}

	public function setListener(?callable $listener) : InvMenu{
		$this->listener = $listener;
		return $this;
	}

	public function setInventoryCloseListener(?callable $listener) : InvMenu{
		$this->inventory_close_listener = $listener;
		return $this;
	}

	public function copyProperties(InvMenu $menu) : void{
		$this->setName($menu->getName())
			->readonly($menu->isReadonly())
			->setListener($menu->listener)
			->setInventoryCloseListener($menu->inventory_close_listener);
	}

	final public function send(Player $player, ?string $name = null) : bool{
		$extradata = PlayerManager::get($player)->getMenuExtradata();
		$extradata->setName($name ?? $this->getName());
		$extradata->setPosition($player->floor()->add(0, static::INVENTORY_HEIGHT, 0));

		$this->type->sendGraphic($player, $extradata);
		return PlayerManager::get($player)->setCurrentMenu($this);
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function sendInventory(Player $player) : bool{
		return $player->setCurrentWindow($this->getInventoryForPlayer($player));
	}

	abstract public function getInventoryForPlayer(Player $player) : InvMenuInventory;

	public function handleInventoryTransaction(Player $player, Item $in, Item $out, SlotChangeAction $action) : bool{
		if($this->listener !== null){
			if($this->readonly){
				($this->listener)($player, $in, $out, $action);
				return false;
			}

			return ($this->listener)($player, $in, $out, $action);
		}

		return true;
	}

	public function onClose(Player $player) : void{
		if($this->inventory_close_listener !== null){
			($this->inventory_close_listener)($player, $this->getInventoryForPlayer($player));
		}

		$session = PlayerManager::get($player);
		$this->type->removeGraphic($player, $session->getMenuExtradata());
		$session->removeCurrentMenu();
	}
}
