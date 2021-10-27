<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic;

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\block\Block;
use pocketmine\block\tile\Spawnable;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

final class BlockInvMenuGraphic implements PositionedInvMenuGraphic{

	private Block $block;
	private Vector3 $position;
	private ?InvMenuGraphicNetworkTranslator $network_translator;

	public function __construct(Block $block, Vector3 $position, ?InvMenuGraphicNetworkTranslator $network_translator = null){
		$this->block = $block;
		$this->position = $position;
		$this->network_translator = $network_translator;
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function send(Player $player, ?string $name) : void{
		$player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3(new Vector3($this->position->x, $this->position->y, $this->position->z)), RuntimeBlockMapping::getInstance()->toRuntimeId($this->block->getFullId()), UpdateBlockPacket::FLAG_NONE, UpdateBlockPacket::DATA_LAYER_NORMAL));
	}

	public function sendInventory(Player $player, Inventory $inventory) : bool{
		return $player->setCurrentWindow($inventory);
	}

	public function remove(Player $player) : void{
		$network = $player->getNetworkSession();
		$world = $player->getWorld();
		$runtime_block_mapping = RuntimeBlockMapping::getInstance();
		$block = $world->getBlockAt($this->position->x, $this->position->y, $this->position->z);
		$network->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3(new Vector3($this->position->x, $this->position->y, $this->position->z)), $runtime_block_mapping->toRuntimeId($block->getFullId()), UpdateBlockPacket::FLAG_NONE, UpdateBlockPacket::DATA_LAYER_NORMAL), true);

		$tile = $world->getTileAt($this->position->x, $this->position->y, $this->position->z);
		if($tile instanceof Spawnable){
			$network->sendDataPacket(BlockActorDataPacket::create(BlockPosition::fromVector3(new Vector3($this->position->x, $this->position->y, $this->position->z)), $tile->getSerializedSpawnCompound()), true);
		}
	}

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator{
		return $this->network_translator;
	}
}