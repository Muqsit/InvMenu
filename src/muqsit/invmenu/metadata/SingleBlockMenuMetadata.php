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
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\tile\Nameable;
use pocketmine\tile\Tile;

class SingleBlockMenuMetadata extends MenuMetadata{

	/** @var NetworkLittleEndianNBTStream */
	protected static $serializer;

	/** @var Block */
	protected $block;

	/** @var string */
	protected $identifier;

	/** @var int */
	protected $size;

	/** @var string */
	protected $tile_id;

	public function __construct(string $identifier, int $size, int $window_type, Block $block, string $tile_id){
		parent::__construct($identifier, $size, $window_type);

		if(self::$serializer === null){
			self::$serializer = new NetworkLittleEndianNBTStream();
		}

		$this->block = $block;
		$this->tile_id = $tile_id;
	}

	public function sendGraphic(Player $player, MenuExtradata $metadata) : void{
		$positions = $this->getBlockPositions($metadata);
		$name = $metadata->getName();
		foreach($positions as $pos){
			$packet = new UpdateBlockPacket();
			$packet->x = $pos->x;
			$packet->y = $pos->y;
			$packet->z = $pos->z;
			$packet->blockRuntimeId = $this->block->getRuntimeId();
			$packet->flags = UpdateBlockPacket::FLAG_NETWORK;
			$player->sendDataPacket($packet);
			$player->sendDataPacket($this->getBlockActorDataPacketAt($player, $pos, $name));
		}
	}

	protected function getBlockActorDataPacketAt(Player $player, Vector3 $pos, ?string $name) : BlockActorDataPacket{
		$packet = new BlockActorDataPacket();
		$packet->x = $pos->x;
		$packet->y = $pos->y;
		$packet->z = $pos->z;
		$packet->namedtag = self::$serializer->write($this->getBlockActorDataAt($pos, $name));
		return $packet;
	}

	protected function getBlockActorDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		$tag = new CompoundTag();
		$tag->setString(Tile::TAG_ID, $this->tile_id);
		if($name !== null){
			$tag->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}
		return $tag;
	}

	public function removeGraphic(Player $player, MenuExtradata $extradata) : void{
		$level = $player->getLevel();
		foreach($this->getBlockPositions($extradata) as $pos){
			$packet = new UpdateBlockPacket();
			$packet->x = $pos->x;
			$packet->y = $pos->y;
			$packet->z = $pos->z;
			$packet->blockRuntimeId = $level->getBlockAt($pos->x, $pos->y, $pos->z)->getRuntimeId();
			$packet->flags = UpdateBlockPacket::FLAG_NETWORK;
			$player->sendDataPacket($packet);
		}
	}

	protected function getBlockPositions(MenuExtradata $metadata) : array{
		return [$metadata->getPosition()];
	}
}