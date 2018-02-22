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
use pocketmine\inventory\BaseInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{CompoundTag, StringTag};
use pocketmine\network\mcpe\protocol\{BlockEntityDataPacket, ContainerClosePacket, ContainerOpenPacket};
use pocketmine\Player;

abstract class BaseFakeInventory extends BaseInventory {

    const SEND_BLOCKS_FAKE = 0;
    const SEND_BLOCKS_REAL = 1;

    const FAKE_BLOCK_ID = 0;
    const FAKE_BLOCK_DATA = 0;
    const FAKE_TILE_ID = "";

    const INVENTORY_HEIGHT = 3;

    /** @var Vector3[] */
    protected $holders = [];

    /** @var InvMenu */
    protected $menu;

    /** @var BigEndianNBTStream|null */
    protected static $nbtWriter;

    public function __construct(InvMenu $menu)
    {
        $this->menu = $menu;
        parent::__construct();
    }

    public function getMenu() : InvMenu
    {
        return $this->menu;
    }

    public function onOpen(Player $player) : void
    {
        if (!isset($this->holders[$id = $player->getId()])) {
            parent::onOpen($player);

            $this->holders[$id] = $player->round()->add(0, self::INVENTORY_HEIGHT);
            $this->sendBlocks($player, self::SEND_BLOCKS_FAKE);

            $this->sendFakeTile($player);
            $this->sendInventoryInterface($player);
        }
    }

    public function onClose(Player $player) : void
    {
        if (isset($this->holders[$id = $player->getId()])) {
            parent::onClose($player);

            $this->sendBlocks($player, self::SEND_BLOCKS_REAL);
            $this->menu->onInventoryClose($player);

            unset($this->holders[$id]);

            $pk = new ContainerClosePacket();
            $pk->windowId = $player->getWindowId($this);
            $player->dataPacket($pk);
        }
    }

    public function sendInventoryInterface(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

        $pk = new ContainerOpenPacket();
        $pk->windowId = $player->getWindowId($this);
        $pk->type = $this->getNetworkType();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $player->dataPacket($pk);

        $this->sendContents($player);
    }

    protected function sendFakeTile(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $writer = self::$nbtWriter ?? (self::$nbtWriter = new NetworkLittleEndianNBTStream());
        $tag = new CompoundTag("", [
            new StringTag("id", static::FAKE_TILE_ID)
        ]);
        $customName = $this->menu->getName();
        if ($customName !== null) {
            $tag->setString("CustomName", $customName);
        }

        $pk->namedtag = $writer->write($tag);
        $player->dataPacket($pk);
    }

    protected function sendBlocks(Player $player, int $type) : void
    {
        switch ($type) {
            case self::SEND_BLOCKS_FAKE:
                $player->getLevel()->sendBlocks([$player], $this->getFakeBlocks($this->holders[$player->getId()]));
                return;
            case self::SEND_BLOCKS_REAL:
                $player->getLevel()->sendBlocks([$player], $this->getRealBlocks($player, $this->holders[$player->getId()]));
                return;
        }

        throw new \Error("Unhandled type $type provided.");
    }

    protected function getFakeBlocks(Vector3 $holder) : array
    {
        return [
            Block::get(static::FAKE_BLOCK_ID, static::FAKE_BLOCK_DATA)->setComponents($holder->x, $holder->y, $holder->z)
        ];
    }

    protected function getRealBlocks(Player $player, Vector3 $holder) : array
    {
        return [
            $player->getLevel()->getBlockAt($holder->x, $holder->y, $holder->z)
        ];
    }
}