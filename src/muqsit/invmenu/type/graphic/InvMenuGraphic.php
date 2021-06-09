<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic;

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuGraphic{

	public function send(Player $player, ?string $name) : void;

	public function sendInventory(Player $player, Inventory $inventory) : bool;

	public function remove(Player $player) : void;

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator;
}