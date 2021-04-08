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
use pocketmine\block\tile\Spawnable;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\world\World;

class SingleBlockMenuMetadata extends MenuMetadata{

	protected Block $block;

	public function __construct(string $identifier, int $size, int $window_type, Block $block){
		parent::__construct($identifier, $size, $window_type);
		$this->block = $block;
	}

	public function sendGraphic(Player $player, MenuExtradata $metadata) : bool{
		$positions = $this->getBlockPositions($metadata);
		if(count($positions) > 0){
			foreach($positions as $pos){
				$this->sendGraphicAt($pos, $player, $metadata);
			}
			return true;
		}
		return false;
	}

	protected function sendGraphicAt(Vector3 $pos, Player $player, MenuExtradata $metadata) : void{
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create($pos->x, $pos->y, $pos->z, RuntimeBlockMapping::getInstance()->toRuntimeId($this->block->getFullId())));
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
		$pos = $metadata->getPositionNotNull();
		return $pos->y >= 0 && $pos->y < World::Y_MAX ? [$pos] : [];
	}
}