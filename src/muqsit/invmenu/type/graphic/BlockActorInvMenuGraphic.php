<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic;

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\block\Block;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\player\Player;

final class BlockActorInvMenuGraphic implements PositionedInvMenuGraphic{

	public static function createTile(string $tile_id, ?string $name) : CompoundTag{
		$tag = CompoundTag::create()->setString(Tile::TAG_ID, $tile_id);
		if($name !== null){
			$tag->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}
		return $tag;
	}

	private BlockInvMenuGraphic $block_graphic;
	private Vector3 $position;
	private CompoundTag $tile;
	private ?InvMenuGraphicNetworkTranslator $network_translator;

	public function __construct(Block $block, Vector3 $position, CompoundTag $tile, ?InvMenuGraphicNetworkTranslator $network_translator = null){
		$this->block_graphic = new BlockInvMenuGraphic($block, $position);
		$this->position = $position;
		$this->tile = $tile;
		$this->network_translator = $network_translator;
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function send(Player $player, ?string $name) : void{
		$this->block_graphic->send($player, $name);
		if($name !== null){
			$this->tile->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}
		$player->getNetworkSession()->sendDataPacket(BlockActorDataPacket::create($this->position->x, $this->position->y, $this->position->z, new CacheableNbt($this->tile)));
	}

	public function sendInventory(Player $player, Inventory $inventory) : bool{
		return $player->setCurrentWindow($inventory);
	}

	public function remove(Player $player) : void{
		$this->block_graphic->remove($player);
	}

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator{
		return $this->network_translator;
	}
}