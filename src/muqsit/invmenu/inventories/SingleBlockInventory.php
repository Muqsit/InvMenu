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

use muqsit\invmenu\utils\HolderData;

use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;

abstract class SingleBlockInventory extends BaseFakeInventory{

	protected function sendFakeBlockData(Player $player, HolderData $data) : void{
		$block = $this->getBlock()->setComponents($data->position->x, $data->position->y, $data->position->z);
		$player->getLevel()->sendBlocks([$player], [$block]);

		$tag = new CompoundTag();
		if($data->custom_name !== null){
			$tag->setString("CustomName", $data->custom_name);
		}

		$this->sendTile($player, $block, $tag);

		$this->onFakeBlockDataSend($player);
	}

	protected function sendRealBlockData(Player $player, HolderData $data) : void{
		$player->getLevel()->sendBlocks([$player], [$data->position]);
	}

	abstract public function getBlock() : Block;
}
