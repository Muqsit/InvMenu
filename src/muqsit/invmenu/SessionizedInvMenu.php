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
use pocketmine\Player;

class SessionizedInvMenu extends InvMenu{

	/** @var SharedInvMenu[] */
	protected $menus = [];

	public function getMenu(Player $player) : SharedInvMenu{
		if(isset($this->menus[$uuid = $player->getRawUniqueId()])){
			return $this->menus[$uuid];
		}

		$this->menus[$uuid] = $menu = new SharedInvMenu($this->type);
		$menu->copyProperties($this);
		return $menu;
	}

	public function getInventory(Player $player) : InvMenuInventory{
		return $this->getMenu($player)->getInventory();
	}

	public function getInventoryForPlayer(Player $player) : InvMenuInventory{
		return $this->getInventory($player);
	}

	public function onClose(Player $player) : void{
		parent::onClose($player);
		unset($this->menus[$player->getRawUniqueId()]);
	}
}