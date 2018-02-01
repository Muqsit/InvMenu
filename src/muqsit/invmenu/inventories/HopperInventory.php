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

use pocketmine\block\BlockIds;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Tile;

class HopperInventory extends BaseFakeInventory {

    const FAKE_BLOCK_ID = BlockIds::HOPPER_BLOCK;
    const FAKE_TILE_ID = "Hopper";//Tile::HOPPER;

    public function getName() : string
    {
        return "HopperInventory";
    }

    public function getDefaultSize() : int
    {
        return 5;
    }

    public function getNetworkType() : int
    {
        return WindowTypes::HOPPER;
    }
}