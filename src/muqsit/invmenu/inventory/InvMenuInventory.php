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

declare(strict_types=1);

namespace muqsit\invmenu\inventory;

use muqsit\invmenu\metadata\MenuMetadata;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\session\PlayerSession;
use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\Player;

class InvMenuInventory extends ContainerInventory{

	/** @var MenuMetadata */
	private $menu_metadata;

	public function __construct(MenuMetadata $menu_metadata){
		$this->menu_metadata = $menu_metadata;
		parent::__construct(new Position(), [], $menu_metadata->getSize());
	}

	public function moveTo(int $x, int $y, int $z) : void{
		$this->holder->setComponents($x, $y, $z);
	}

	final public function getMenuMetadata() : MenuMetadata{
		return $this->menu_metadata;
	}

	final public function getName() : string{
		// The value of this does not ALTER the title of the inventory.
		// Use InvMenu::setName() to set the inventory's name, or supply the
		// name parameter in InvMenu::send().
		return $this->menu_metadata->getIdentifier();
	}

	public function getDefaultSize() : int{
		return $this->menu_metadata->getSize();
	}

	public function getNetworkType() : int{
		return $this->menu_metadata->getWindowType();
	}

	public function onClose(Player $who) : void{
		if(isset($this->viewers[spl_object_hash($who)])){
			parent::onClose($who);
			/** @var PlayerSession $session */
			$session = PlayerManager::get($who);
			/** @noinspection NullPointerExceptionInspection */
			$session->getCurrentMenu()->onClose($who);
			$this->menu_metadata->removeGraphic($who, $session->getMenuExtradata());
		}
	}
}