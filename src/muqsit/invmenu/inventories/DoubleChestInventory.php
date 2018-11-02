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

class DoubleChestInventory extends BaseFakeInventory{

	public function getSendDelay(Player $player) : int{
		/**
		 * For those who are confused as to why this even exists...
		 *   The client takes time to render the two chests "pairing" into a double chest.
		 *   If the inventory is directly sent without a delay, the client either gets sent
		 *   a single chest inventory or the client closes the inventory as soon as it renders
		 *   the pair.
		 */
		return $player->getPing() < 300 ? 5 : 0;
	}

	protected function sendFakeBlockData(Player $player, HolderData $data) : void{
		$block = Block::get(Block::CHEST)->setComponents($data->position->x, $data->position->y, $data->position->z);
		$block2 = Block::get(Block::CHEST)->setComponents($data->position->x + 1, $data->position->y, $data->position->z);

		$player->getLevel()->sendBlocks([$player], [$block, $block2]);

		$tag = new CompoundTag();
		if($data->custom_name !== null){
			$tag->setString("CustomName", $data->custom_name);
		}

		$tag->setInt("pairz", $block->z);

		$tag->setInt("pairx", $block->x + 1);
		$this->sendTile($player, $block, $tag);

		$tag->setInt("pairx", $block->x);
		$this->sendTile($player, $block2, $tag);

		$this->onFakeBlockDataSend($player);
	}

	protected function sendRealBlockData(Player $player, HolderData $data) : void{
		$player->getLevel()->sendBlocks([$player], [$data->position, $data->position->add(1, 0, 0)]);
	}

	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	public function getTileId() : string{
		return Tile::CHEST;
	}

	public function getName() : string{
		return "Chest";
	}

	public function getDefaultSize() : int{
		return 54;
	}
}