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

namespace muqsit\invmenu\inventory;

use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryChangeListener;

class SharedInventorySynchronizer implements InventoryChangeListener{

	/** @var SharedInvMenu */
	protected $menu;

	public function __construct(SharedInvMenu $menu){
		$this->menu = $menu;
	}

	public function onContentChange(Inventory $inventory) : void{
		$this->menu->getInventory()->setContents($inventory->getContents());
	}

	public function onSlotChange(Inventory $inventory, int $slot) : void{
		$this->menu->getInventory()->setItem($slot, $inventory->getItem($slot));
	}
}