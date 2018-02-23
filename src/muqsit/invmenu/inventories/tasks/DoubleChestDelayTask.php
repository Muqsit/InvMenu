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

namespace muqsit\invmenu\inventories\tasks;

use muqsit\invmenu\inventories\DoubleChestInventory;

use pocketmine\Player;
use pocketmine\scheduler\Task;

class DoubleChestDelayTask extends Task {

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
}