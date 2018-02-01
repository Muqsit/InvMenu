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
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{CompoundTag, IntTag, StringTag};
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\scheduler\Task;
use pocketmine\Player;

class DoubleChestInventory extends ChestInventory {

    public function getName() : string
    {
        return "DoubleChestInventory";
    }

    public function getDefaultSize() : int
    {
        return 54;
    }

    public function sendInventoryInterface(Player $player, bool $force = false) : void
    {
        if (!$force && $player->getPing() < 300) {//if you have > 300 ping, thank your network connection for providing you the delay
            $player->getServer()->getScheduler()->scheduleDelayedTask(new class($player, $this) extends Task {

                /** @var Player */
                private $player;

                /** @var DoubleChestInventory */
                private $inventory;

                public function __construct(Player $player, DoubleChestInventory $inventory)
                {
                    $this->player = $player;
                    $this->inventory = $inventory;
                }

                public function onRun(int $tick) : void
                {
                    if ($this->player->isAlive()) {
                        $this->inventory->sendInventoryInterface($this->player, true);
                    }
                }
            }, 4);
            return;
        }

        parent::sendInventoryInterface($player);
    }

    protected function sendFakeTile(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $writer = self::$nbtWriter ?? (self::$nbtWriter = new NetworkLittleEndianNBTStream());
        $nbt = new CompoundTag("", [
            new StringTag("id", static::FAKE_TILE_ID),
            new IntTag("pairx", $holder->x + 1),
            new IntTag("pairz", $holder->z)
        ]);
        $customName = $this->menu->getName();
        if ($customName !== null) {
            $nbt->setString("CustomName", $customName);
        }
        $writer->setData($nbt);

        $pk->namedtag = $writer->write();
        $player->dataPacket($pk);

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x + 1;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $writer->setData(new CompoundTag("", [
            new StringTag("id", static::FAKE_TILE_ID),
            new IntTag("pairx", $holder->x),
            new IntTag("pairz", $holder->z)
        ]));

        $pk->namedtag = $writer->write();
        $player->dataPacket($pk);
    }

    protected function getFakeBlocks(Vector3 $holder) : array
    {
        return array_merge(parent::getFakeBlocks($holder), [
            Block::get(static::FAKE_BLOCK_ID, static::FAKE_BLOCK_DATA)->setComponents($holder->x + 1, $holder->y, $holder->z),
        ]);
    }

    protected function getRealBlocks(Player $player, Vector3 $holder) : array
    {
        return array_merge(parent::getRealBlocks($player, $holder), [
            $player->getLevel()->getBlockAt($holder->x + 1, $holder->y, $holder->z)
        ]);
    }
}