<?php
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