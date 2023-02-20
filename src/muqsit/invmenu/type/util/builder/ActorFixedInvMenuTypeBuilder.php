<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util\builder;

use muqsit\invmenu\type\ActorFixedInvMenuType;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

final class ActorFixedInvMenuTypeBuilder implements InvMenuTypeBuilder{
	use ActorInvMenuTypeBuilderTrait;
	use FixedInvMenuTypeBuilderTrait{ setSize as parentSetSize; }
	use GraphicNetworkTranslatableInvMenuTypeBuilderTrait{ setNetworkWindowType as parentSetNetworkWindowType; }

	public function __construct(){
		$metadata = $this->getActorMetadata();
		$metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.01);
		$metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.01);
		$metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
	}

	public function setNetworkWindowType(int $window_type) : self{
		$this->parentSetNetworkWindowType($window_type);
		$this->getActorMetadata()->setByte(EntityMetadataProperties::CONTAINER_TYPE, $window_type);
		return $this;
	}

	public function setSize(int $size) : self{
		$this->parentSetSize($size);
		$this->getActorMetadata()->setInt(EntityMetadataProperties::CONTAINER_BASE_SIZE, $size);
		return $this;
	}

	public function build() : ActorFixedInvMenuType{
		return new ActorFixedInvMenuType($this->getActorIdentifier(), $this->getActorRuntimeIdentifier(), $this->getActorMetadata()->getAll(), $this->getSize(), $this->getGraphicNetworkTranslator());
	}
}