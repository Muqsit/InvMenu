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

namespace muqsit\invmenu\session;

use pocketmine\math\Vector3;

class MenuExtradata{

	/** @var Vector3|null */
	protected $position;

	/** @var string|null */
	protected $name;

	public function getPosition() : ?Vector3{
		return $this->position;
	}

	public function getName() : ?string{
		return $this->name;
	}

	public function setPosition(?Vector3 $pos) : void{
		$this->position = $pos;
	}

	public function setName(?string $name) : void{
		$this->name = $name;
	}

	public function reset() : void{
		$this->position = null;
		$this->name = null;
	}
}