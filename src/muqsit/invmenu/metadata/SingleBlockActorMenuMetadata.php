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
use pocketmine\Player;
use pocketmine\tile\Nameable;
use pocketmine\tile\Tile;

class SingleBlockActorMenuMetadata extends SingleBlockMenuMetadata{

	/** @var NetworkLittleEndianNBTStream */
	protected static $serializer;

	/** @var string */
	protected $tile_id;

	public function __construct(string $identifier, int $size, int $window_type, Block $block, string $tile_id){
		parent::__construct($identifier, $size, $window_type, $block);

		if(self::$serializer === null){
			self::$serializer = new NetworkLittleEndianNBTStream();
		}

		$this->tile_id = $tile_id;
	}

	protected function sendGraphicAt(Vector3 $pos, Player $player, MenuExtradata $metadata) : void{
		parent::sendGraphicAt($pos, $player, $metadata);
		$player->sendDataPacket($this->getBlockActorDataPacketAt($player, $pos, $metadata->getName()));
	}

	protected function getBlockActorDataPacketAt(Player $player, Vector3 $pos, ?string $name) : BlockActorDataPacket{
		$packet = new BlockActorDataPacket();
		$packet->x = $pos->x;
		$packet->y = $pos->y;
		$packet->z = $pos->z;

		$namedtag = self::$serializer->write($this->getBlockActorDataAt($pos, $name));
		assert($namedtag !== false);

		$packet->namedtag = $namedtag;
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
}