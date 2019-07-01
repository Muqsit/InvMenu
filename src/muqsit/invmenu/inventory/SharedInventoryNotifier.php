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
use pocketmine\inventory\InventoryChangeListener;

class SharedInventoryNotifier implements InventoryChangeListener{

	/** @var Inventory */
	protected $inventory;

	/** @var SharedInventorySynchronizer */
	protected $synchronizer;

	public function __construct(Inventory $inventory, SharedInventorySynchronizer $synchronizer){
		$this->inventory = $inventory;
		$this->synchronizer = $synchronizer;
	}

	public function onContentChange(Inventory $inventory) : void{
		$this->inventory->removeChangeListeners($this->synchronizer);
		$this->inventory->setContents($inventory->getContents());
		$this->inventory->addChangeListeners($this->synchronizer);
	}

	public function onSlotChange(Inventory $inventory, int $slot) : void{
		if($slot < $inventory->getSize()){
			$this->inventory->removeChangeListeners($this->synchronizer);
			$this->inventory->setItem($slot, $inventory->getItem($slot));
			$this->inventory->addChangeListeners($this->synchronizer);
		}
	}
}