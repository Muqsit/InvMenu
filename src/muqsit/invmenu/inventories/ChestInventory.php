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

use muqsit\invmenu\InvMenu;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\TileFactory;

class ChestInventory extends SingleBlockInventory{

	public function __construct(InvMenu $menu, array $items = []){
		parent::__construct($menu, 27, $items);
	}

	public function getBlock() : Block{
		return Block::get(Block::CHEST);
	}

	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	public function getTileId() : string{
		return TileFactory::getSaveId(Chest::class);
	}

	public function getName() : string{
		return "Chest";
	}
}
