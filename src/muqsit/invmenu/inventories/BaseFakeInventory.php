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

namespace muqsit\invmenu\inventories;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\tasks\DelayedFakeBlockDataNotifyTask;
use muqsit\invmenu\utils\HolderData;

use pocketmine\block\Block;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\Player;

abstract class BaseFakeInventory extends ContainerInventory{

	const INVENTORY_HEIGHT = 3;

	/** @var InvMenu */
	protected $menu;

	/** @var int */
	protected $default_send_delay = 0;

	/** @var HolderData[] */
	private $holder_data = [];

	public function __construct(InvMenu $menu, array $items = [], int $size = null, string $title = null){
		$this->menu = $menu;
		BaseInventory::__construct($items, $size, $title);
	}

	public function getMenu() : InvMenu{
		return $this->menu;
	}

	public function createNewInstance(InvMenu $menu) : BaseFakeInventory{
		return new static($menu, $this->getContents());
	}

	final public function send(Player $player, ?string $custom_name) : bool{
		$position = $player->floor()->add(0, static::INVENTORY_HEIGHT, 0);
		if($player->getLevel()->isInWorld($position->x, $position->y, $position->z)){
			$this->sendFakeBlockData($player, $this->holder_data[$player->getId()] = new HolderData($position, $custom_name));
			return true;
		}

		return false;
	}

	final public function open(Player $player) : bool{
		if(!isset($this->holder_data[$player->getId()])){
			return false;
		}

		return parent::open($player);
	}

	final public function onOpen(Player $player) : void{
		$this->holder = $this->holder_data[$player->getId()]->position;
		parent::onOpen($player);
		$this->holder = null;
	}

	final public function onClose(Player $player) : void{
		if(isset($this->holder_data[$id = $player->getId()])){
			$pos = $this->holder_data[$id]->position;
			if($player->getLevel()->isChunkLoaded($pos->x >> 4, $pos->z >> 4)){
				$this->sendRealBlockData($player, $this->holder_data[$id]);
			}
			unset($this->holder_data[$id]);

			parent::onClose($player);

			$this->menu->onInventoryClose($player);

			$listener = $this->menu->getInventoryCloseListener();
			if($listener !== null){
				$listener($player, $this);
			}
		}
	}

	abstract protected function sendFakeBlockData(Player $player, HolderData $data) : void;

	abstract protected function sendRealBlockData(Player $player, HolderData $data) : void;

	abstract public function getTileId() : string;

	public function getSendDelay(Player $player) : int{
		return $this->default_send_delay;
	}

	public function setDefaultSendDelay(int $delay) : void{
		$this->default_send_delay = $delay;
	}

	public function onFakeBlockDataSend(Player $player) : void{
		$delay = $this->getSendDelay($player);
		if($delay > 0){
			InvMenuHandler::getRegistrant()->getScheduler()->scheduleDelayedTask(new DelayedFakeBlockDataNotifyTask($player, $this), $delay);
		}else{
			$this->onFakeBlockDataSendSuccess($player);
		}
	}

	public function onFakeBlockDataSendSuccess(Player $player) : void{
		$player->addWindow($this);
	}

	public function onFakeBlockDataSendFailed(Player $player) : void{
		unset($this->holder_data[$player->getId()]);
	}

	protected function sendTile(Player $player, Vector3 $pos, CompoundTag $nbt) : void{
		$nbt->setString("id", $this->getTileId());

		$pk = new BlockActorDataPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->namedtag = (new NetworkLittleEndianNBTStream())->write($nbt);
		$player->sendDataPacket($pk);
	}
}
