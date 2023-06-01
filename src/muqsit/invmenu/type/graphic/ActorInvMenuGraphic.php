<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic;

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;

final class ActorInvMenuGraphic implements InvMenuGraphic{

	/**
	 * @param string $actor_identifier
	 * @param int $actor_runtime_identifier
	 * @param array<int, MetadataProperty> $actor_metadata
	 * @param InvMenuGraphicNetworkTranslator|null $network_translator
	 * @param int $animation_duration
	 */
	public function __construct(
		readonly private string $actor_identifier,
		readonly private int $actor_runtime_identifier,
		readonly private array $actor_metadata,
		readonly private ?InvMenuGraphicNetworkTranslator $network_translator = null,
		readonly private int $animation_duration = 0
	){}

	public function send(Player $player, ?string $name) : void{
		$metadata = $this->actor_metadata;
		if($name !== null){
			$metadata[EntityMetadataProperties::NAMETAG] = new StringMetadataProperty($name);
		}
		$player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
			$this->actor_runtime_identifier,
			$this->actor_runtime_identifier,
			$this->actor_identifier,
			$player->getPosition()->asVector3(),
			null,
			0.0,
			0.0,
			0.0,
			0.0,
			[],
			$metadata,
			new PropertySyncData([], []),
			[]
		));
	}

	public function sendInventory(Player $player, Inventory $inventory) : bool{
		return $player->setCurrentWindow($inventory);
	}

	public function remove(Player $player) : void{
		$player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->actor_runtime_identifier));
	}

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator{
		return $this->network_translator;
	}

	public function getAnimationDuration() : int{
		return $this->animation_duration;
	}
}