<?php

declare(strict_types=1);

namespace muqsit\invmenu\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

final class SharedInventoryNotifier implements InventoryListener{

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