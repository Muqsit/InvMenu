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

class SharedInventoryNotifier implements InventoryListener{

	protected Inventory $inventory;
	protected SharedInventorySynchronizer $synchronizer;

	public function __construct(Inventory $inventory, SharedInventorySynchronizer $synchronizer){
		$this->inventory = $inventory;
		$this->synchronizer = $synchronizer;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$this->inventory->getListeners()->remove($this->synchronizer);
		$this->inventory->setContents($inventory->getContents());
		$this->inventory->getListeners()->add($this->synchronizer);
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		if($slot < $inventory->getSize()){
			$this->inventory->getListeners()->remove($this->synchronizer);
			$this->inventory->setItem($slot, $inventory->getItem($slot));
			$this->inventory->getListeners()->add($this->synchronizer);
		}
	}
}