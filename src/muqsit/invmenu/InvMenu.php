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

namespace muqsit\invmenu;

use muqsit\invmenu\inventories\{
    BaseFakeInventory, ChestInventory, DoubleChestInventory, HopperInventory
};

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\MainLogger;

class InvMenu {

    const TYPE_CUSTOM = -1;
    const TYPE_CHEST = 0;
    const TYPE_HOPPER = 1;
    const TYPE_DOUBLE_CHEST = 2;

    const INVENTORY_CLASSES = [
        self::TYPE_CHEST => ChestInventory::class,
        self::TYPE_HOPPER => HopperInventory::class,
        self::TYPE_DOUBLE_CHEST => DoubleChestInventory::class
    ];

    public static function create(int $windowId, ?string $customInvClass = null) : InvMenu
    {
        return new InvMenu($windowId, $customInvClass);
    }

    /** @var int */
    private $type;

    /** @var string|null */
    private $name;

    /** @var BaseFakeInventory|null */
    private $inventory;

    /** @var string */
    private $inventory_class;

    /** @var bool */
    private $readonly = false;

    /** @var bool */
    private $sessionize = false;

    /** @var BaseFakeInventory[]|null */
    private $sessions;

    /** @var callable|null */
    private $listener;

    /** @var callable|null */
    private $inventoryCloseListener;

    private function __construct(int $type, ?string $customInvClass = null)
    {
        if ($type === self::TYPE_CUSTOM) {
            if ($customInvClass === null) {
                throw new \Error("You need to specify a custom inventory class if you are creating InvMenu with custom type.");
            }
            if (!is_subclass_of($customInvClass, BaseFakeInventory::class, true)) {
                throw new \Error("$customInvClass must extend ".BaseFakeInventory::class.".");
            }
            $class = $customInvClass;
        } else {
            if ($customInvClass !== null) {
                MainLogger::getLogger()->warning("A custom inventory class was specified for an InvMenu. This class will not be used as the inventory class unless you specify the menu type as TYPE_CUSTOM.");
            }
            $class = self::INVENTORY_CLASSES[$type];
        }

        $this->inventory_class = $class;
        $this->type = $type;

        if (!$this->sessionize) {
            $this->inventory = $this->createNewInventoryInstance();
        }
    }

    public function createNewInventoryInstance() : BaseFakeInventory
    {
        $class = $this->inventory_class;
        return new $class($this);
    }

    public function getInventory(?Player $player = null) : BaseFakeInventory
    {
        if ($this->sessionize) {
            if ($player === null) {
                throw new \Error("Could not select the base inventory when InvMenu is sessionized. Please specify a Player instance in the first parameter.");
            }

            return $this->sessions[$player->getId()] ?? ($this->sessions[$player->getId()] = $this->createNewInventoryInstance());
        }
        return $this->inventory;
    }

    public function readonly(bool $value = true) : InvMenu
    {
        $this->readonly = $value;
        return $this;
    }

    public function isReadonly() : bool
    {
        return $this->readonly;
    }

    public function setListener(callable $listener) : InvMenu
    {
        if (!InvMenuHandler::isRegistered()) {
            throw new \Error("Attempted to assign a listener without InvMenuHandler being registered. Use InvMenuHandler::register() if you want to handle inventory transactions.");
        }

        $this->listener = $listener;
        return $this;
    }

    public function isListenable() : bool
    {
        return $this->listener !== null;
    }

    public function getListener() : ?callable
    {
        return $this->listener;
    }

    public function setInventoryCloseListener(callable $listener) : InvMenu
    {
        $this->inventoryCloseListener = $listener;
        return $this;
    }

    public function sessionize() : InvMenu
    {
        $this->sessionize = true;
        $this->sessions = [];
        return $this;
    }

    public function setName(?string $name = null) : InvMenu
    {
        $this->name = $name;
        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function send(Player $player, ?int $forceId = null) : int
    {
        return $player->addWindow($this->getInventory($player), $forceId);
    }

    public function onInventoryClose(Player $player) : void
    {
        if ($this->inventoryCloseListener !== null) {
            ($this->inventoryCloseListener)($player, $this->getInventory($player));
        }
        if ($this->sessionize) {
            unset($this->sessions[$player->getId()]);
        }
    }

    public function __clone()
    {
        $contents = $this->inventory->getContents();
        $this->inventory = $this->createNewInventoryInstance();
        $this->inventory->setContents($contents);
    }
}
