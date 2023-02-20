<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util\builder;

use muqsit\invmenu\type\graphic\network\ActorInvMenuGraphicNetworkTranslator;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;

trait ActorInvMenuTypeBuilderTrait{

	private ?string $actor_identifier = null;
	private ?int $actor_runtime_identifier = null;
	private ?EntityMetadataCollection $actor_metadata = null;

	public function getActorRuntimeIdentifier() : int{
		return $this->actor_runtime_identifier ?? $this->setActorRuntimeIdentifier(Entity::nextRuntimeId())->getActorRuntimeIdentifier();
	}

	public function setActorRuntimeIdentifier(int $actor_runtime_identifier) : self{
		$this->actor_runtime_identifier = $actor_runtime_identifier;
		$this->addGraphicNetworkTranslator(new ActorInvMenuGraphicNetworkTranslator($this->actor_runtime_identifier));
		return $this;
	}

	public function getActorMetadata() : EntityMetadataCollection{
		return $this->actor_metadata ?? $this->setActorMetadata(new EntityMetadataCollection())->getActorMetadata();
	}

	public function setActorMetadata(EntityMetadataCollection $actor_metadata) : self{
		$this->actor_metadata = $actor_metadata;
		return $this;
	}

	public function getActorIdentifier() : string{
		return $this->actor_identifier ?? $this->setActorIdentifier(EntityIds::CHEST_MINECART)->getActorIdentifier();
	}

	public function setActorIdentifier(string $actor_identifier) : self{
		$this->actor_identifier = $actor_identifier;
		return $this;
	}
}