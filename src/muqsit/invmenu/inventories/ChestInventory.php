<?php
namespace muqsit\invmenu\inventories;

use pocketmine\block\BlockIds;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Tile;

class ChestInventory extends BaseFakeInventory {

    const FAKE_BLOCK_ID = BlockIds::CHEST;
    const FAKE_TILE_ID = Tile::CHEST;

    public function getName() : string
    {
        return "ChestInventory";
    }

    public function getDefaultSize() : int
    {
        return 27;
    }

    public function getNetworkType() : int
    {
        return WindowTypes::CONTAINER;
    }
}