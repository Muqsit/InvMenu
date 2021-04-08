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

namespace muqsit\invmenu\metadata;

use muqsit\invmenu\session\MenuExtradata;
use pocketmine\block\Block;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\player\Player;

class SingleBlockActorMenuMetadata extends SingleBlockMenuMetadata{

	protected string $tile_id;

	public function __construct(string $identifier, int $size, int $window_type, Block $block, string $tile_id){
		parent::__construct($identifier, $size, $window_type, $block);
		$this->tile_id = $tile_id;
	}

	protected function sendGraphicAt(Vector3 $pos, Player $player, MenuExtradata $metadata) : void{
		parent::sendGraphicAt($pos, $player, $metadata);
		$player->getNetworkSession()->sendDataPacket($this->getBlockActorDataPacketAt($player, $pos, $metadata->getName()));
	}

	protected function getBlockActorDataPacketAt(Player $player, Vector3 $pos, ?string $name) : BlockActorDataPacket{
		return BlockActorDataPacket::create(
			$pos->x,
			$pos->y,
			$pos->z,
			new CacheableNbt($this->getBlockActorDataAt($pos, $name))
		);
	}

	protected function getBlockActorDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		$tag = CompoundTag::create()->setString(Tile::TAG_ID, $this->tile_id);
		$tag->setInt(Tile::TAG_X, $pos->x);
		$tag->setInt(Tile::TAG_Y, $pos->y);
		$tag->setInt(Tile::TAG_Z, $pos->z);
		if($name !== null){
			$tag->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}
		return $tag;
	}
}