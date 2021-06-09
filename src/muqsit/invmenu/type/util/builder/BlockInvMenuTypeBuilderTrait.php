<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util\builder;

use InvalidStateException;
use pocketmine\block\Block;

trait BlockInvMenuTypeBuilderTrait{

	private ?Block $block = null;

	public function setBlock(Block $block) : self{
		$this->block = $block;
		return $this;
	}

	protected function getBlock() : Block{
		if($this->block === null){
			throw new InvalidStateException("No block was provided");
		}

		return $this->block;
	}
}