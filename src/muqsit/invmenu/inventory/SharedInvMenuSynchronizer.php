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

class SharedInvMenuSynchronizer{

	/** @var Inventory */
	protected $inventory;

	/** @var SharedInventorySynchronizer */
	protected $synchronizer;

	/** @var SharedInventoryNotifier */
	protected $notifier;

	public function __construct(SharedInvMenu $menu, Inventory $inventory){
		$this->inventory = $inventory;

		$menu_inventory = $menu->getInventory();
		$this->synchronizer = new SharedInventorySynchronizer($menu_inventory);
		$inventory->addChangeListeners($this->synchronizer);

		$this->notifier = new SharedInventoryNotifier($this->inventory, $this->synchronizer);
		$menu_inventory->setContents($inventory->getContents());
		$menu_inventory->addChangeListeners($this->notifier);
	}

	public function destroy() : void{
		$this->synchronizer->getSynchronizingInventory()->removeChangeListeners($this->notifier);
		$this->inventory->removeChangeListeners($this->synchronizer);
	}
}