<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util\builder;

use InvalidStateException;

trait FixedInvMenuTypeBuilderTrait{

	private ?int $size = null;

	public function setSize(int $size) : self{
		$this->size = $size;
		return $this;
	}

	protected function getSize() : int{
		if($this->size === null){
			throw new InvalidStateException("No size was provided");
		}

		return $this->size;
	}
}