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

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

class SharedInventorySynchronizer implements InventoryListener{

	protected InvMenuInventory $inventory;

	public function __construct(InvMenuInventory $inventory){
		$this->inventory = $inventory;
	}

	public function getSynchronizingInventory() : InvMenuInventory{
		return $this->inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$this->inventory->setContents($inventory->getContents());
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		$this->inventory->setItem($slot, $inventory->getItem($slot));
	}
}