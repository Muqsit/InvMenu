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

namespace muqsit\invmenu\session;

use muqsit\invmenu\InvMenu;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\player\Player;

class PlayerSession{

	/** @var Player */
	protected $player;

	/** @var MenuExtradata */
	protected $menu_extradata;

	/** @var InvMenu|null */
	protected $current_menu;

	/** @var int|null */
	protected $notification_id;

	public function __construct(Player $player){
		$this->player = $player;
		$this->menu_extradata = new MenuExtradata();
	}

	/**
	 * @internal
	 */
	public function finalize() : void{
		if($this->current_menu !== null){
			$this->player->removeCurrentWindow();
		}
	}

	public function getMenuExtradata() : MenuExtradata{
		return $this->menu_extradata;
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param InvMenu|null $menu
	 * @return bool
	 */
	public function setCurrentMenu(?InvMenu $menu) : bool{
		if($menu !== null && !$this->waitForNotification(mt_rand() * 1000)){ // TODO: remove the x1000 hack when fixed
			return false;
		}

		$this->current_menu = $menu;
		return true;
	}

	protected function waitForNotification(int $notification_id) : bool{
		$pk = new NetworkStackLatencyPacket();
		$pk->timestamp = $notification_id;
		$pk->needResponse = true;

		if($this->player->sendDataPacket($pk)){
			$this->notification_id = $notification_id;
			return true;
		}

		return false;
	}

	public function notify(int $notification_id) : void{
		if($notification_id === $this->notification_id){
			$this->notification_id = null;
			if($this->current_menu !== null){
				if($this->current_menu->sendInventory($this->player)){
					// TODO: Revert this to the Inventory->moveTo() method when it's possible
					// for plugins to specify network type for inventories
					$this->player->sendDataPacket(ContainerOpenPacket::blockInvVec3(
						$this->player->getNetworkSession()->getInvManager()->getCurrentWindowId(),
						$this->current_menu->getType()->getWindowType(),
						$this->menu_extradata->getPosition()
					));
					$this->player->getNetworkSession()->getInvManager()->syncContents($this->current_menu->getInventoryForPlayer($this->player));
				}else{
					$this->setCurrentMenu(null);
				}
			}
		}
	}

	public function getCurrentMenu() : ?InvMenu{
		return $this->current_menu;
	}

	/**
	 * @internal use Player::removeCurrentWindow() instead
	 * @return bool
	 */
	public function removeCurrentMenu() : bool{
		return $this->setCurrentMenu(null);
	}
}
