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

namespace muqsit\invmenu\inventories;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class HopperInventory extends SingleBlockInventory{

	public function getBlock() : Block{
		return Block::get(Block::HOPPER_BLOCK);
	}

	public function getNetworkType() : int{
		return WindowTypes::HOPPER;
	}

	public function getTileId() : string{
		return "Hopper";
	}

	public function getName() : string{
		return "Hopper";
	}

	public function getDefaultSize() : int{
		return 5;
	}
}