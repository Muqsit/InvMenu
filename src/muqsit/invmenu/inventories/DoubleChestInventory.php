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

use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\inventories\tasks\DoubleChestDelayTask;

use pocketmine\block\Block;
use pocketmine\inventory\{BaseInventory, ContainerInventory};
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
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

    public function onOpen(Player $player, bool $force = false) : void
    {
        if (!$force && $player->getPing() < 300) {//if you have > 300 ping, thank your network connection for providing you the delay
            /* For everyone confused what the heck is the reason for the delay's existence.
             * Calling DoubleChestInventory::sendInventoryInterface() just after sending the client
             * the chest block and the chest tile packets will send a normal (27 slot) chest to the client.
             * Delaying it solves the issue with that. The client takes a couple of milliseconds to "merge"
             * the two chests. Please make a PR if you know how to avoid this delay, because it's an utter mess.
             */

            $this->holders[$player->getId()] = $this->holder = $player->floor()->add(0, static::INVENTORY_HEIGHT, 0);

            $this->sendBlocks($player, self::SEND_BLOCKS_FAKE);
            $this->sendFakeTile($player);

            InvMenuHandler::getRegistrant()->getScheduler()->scheduleDelayedTask(new DoubleChestDelayTask($player, $this), 4);
            BaseInventory::onOpen($player);
            return;
        }

        ContainerInventory::onOpen($player);
    }

    protected function sendFakeTile(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $writer = self::$nbtWriter ?? (self::$nbtWriter = new NetworkLittleEndianNBTStream());

        $tag = new CompoundTag();
        $tag->setString("id", static::FAKE_TILE_ID);
        $tag->setInt("pairx", $holder->x + 1);
        $tag->setInt("pairz", $holder->z);

        $customName = $this->menu->getName();
        if ($customName !== null) {
            $tag->setString("CustomName", $customName);
        }

        $pk->namedtag = $writer->write($tag);
        $player->dataPacket($pk);

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x + 1;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $tag->setInt("pairx", $holder->x);

        $pk->namedtag = $writer->write($tag);
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
