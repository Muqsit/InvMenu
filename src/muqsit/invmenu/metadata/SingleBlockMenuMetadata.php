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
use pocketmine\block\tile\Spawnable;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

class SingleBlockMenuMetadata extends MenuMetadata{

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
		$this->block = $block;
		$this->tile_id = $tile_id;
	}

	public function sendGraphic(Player $player, MenuExtradata $metadata) : void{
		$positions = $this->getBlockPositions($metadata);
		$name = $metadata->getName();
		$network = $player->getNetworkSession();
		$block_runtime_id = RuntimeBlockMapping::getInstance()->toRuntimeId($this->block->getFullId());
		foreach($positions as $pos){
			$network->sendDataPacket(UpdateBlockPacket::create($pos->x, $pos->y, $pos->z, $block_runtime_id));
			$network->sendDataPacket($this->getBlockActorDataPacketAt($player, $pos, $name));
		}
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

	public function removeGraphic(Player $player, MenuExtradata $extradata) : void{
		$network = $player->getNetworkSession();
		$world = $player->getWorld();
		$runtime_block_mapping = RuntimeBlockMapping::getInstance();
		foreach($this->getBlockPositions($extradata) as $position){
			$block = $world->getBlockAt($position->x, $position->y, $position->z);
			$network->sendDataPacket(UpdateBlockPacket::create($position->x, $position->y, $position->z, $runtime_block_mapping->toRuntimeId($block->getFullId())), true);

			$tile = $world->getTileAt($position->x, $position->y, $position->z);
			if($tile instanceof Spawnable){
				$network->sendDataPacket(BlockActorDataPacket::create($position->x, $position->y, $position->z, $tile->getSerializedSpawnCompound()), true);
			}
		}
	}

	/**
	 * @param MenuExtradata $metadata
	 * @return Vector3[]
	 */
	protected function getBlockPositions(MenuExtradata $metadata) : array{
		return [$metadata->getPositionNotNull()];
	}
}
