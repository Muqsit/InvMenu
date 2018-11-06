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

namespace muqsit\invmenu\tasks;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\Player;
use pocketmine\scheduler\Task;

class DelayedFakeBlockDataNotifyTask extends Task{

	/** @var Player */
	private $player;

	/** @var BaseFakeInventory */
	private $inventory;

	public function __construct(Player $player, BaseFakeInventory $inventory){
		$this->player = $player;
		$this->inventory = $inventory;
	}

	public function onRun(int $tick) : void{
		if($this->player->isConnected()){
			$this->inventory->onFakeBlockDataSendSuccess($this->player);
		}else{
			$this->inventory->onFakeBlockDataSendFailed($this->player);
		}
	}
}