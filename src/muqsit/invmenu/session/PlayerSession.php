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

use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\session\network\PlayerNetwork;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\player\Player;

class PlayerSession{

	/** @var Player */
	protected $player;

	/** @var PlayerNetwork */
	protected $network;

	/** @var MenuExtradata */
	protected $menu_extradata;

	/** @var InvMenu|null */
	protected $current_menu;

	public function __construct(Player $player, PlayerNetwork $network){
		$this->player = $player;
		$this->network = $network;
		$this->menu_extradata = new MenuExtradata();
	}

	/**
	 * @internal
	 */
	public function finalize() : void{
		if($this->current_menu !== null){
			$this->player->removeCurrentWindow();
		}
		$this->network->dropPending();
	}

	public function getMenuExtradata() : MenuExtradata{
		return $this->menu_extradata;
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param InvMenu|null $menu
	 * @param Closure|null $callback
	 */
	public function setCurrentMenu(?InvMenu $menu, ?Closure $callback = null) : void{
		$this->current_menu = $menu;

		if($this->current_menu !== null){
			$this->network->waitUntil($this->network->getGraphicWaitDuration(), function(bool $success) use ($callback) : void{
				if($this->current_menu !== null){
					if($success && $this->current_menu->sendInventory($this->player)){
						// TODO: Revert this to the Inventory->moveTo() method when it's possible
						// for plugins to specify network type for inventories
						if($this->player->getNetworkSession()->sendDataPacket(ContainerOpenPacket::blockInvVec3(
							$this->player->getNetworkSession()->getInvManager()->getCurrentWindowId(),
							$this->current_menu->getType()->getWindowType(),
							$this->menu_extradata->getPosition()
						))){
							$this->player->getNetworkSession()->getInvManager()->syncContents($this->current_menu->getInventory());
							if($callback !== null){
								$callback(true);
							}
							return;
						}
						$this->player->removeCurrentWindow();
					}else{
						$this->removeCurrentMenu();
					}

					if($callback !== null){
						$callback(false);
					}
				}
			});
		}else{
			$this->network->wait($callback ?? static function(bool $success) : void{});
		}
	}

	public function getNetwork() : PlayerNetwork{
		return $this->network;
	}

	public function getCurrentMenu() : ?InvMenu{
		return $this->current_menu;
	}

	/**
	 * @internal use Player::removeCurrentWindow() instead
	 * @return bool
	 */
	public function removeCurrentMenu() : bool{
		if($this->current_menu !== null){
			$this->current_menu->getType()->removeGraphic($this->player, $this->menu_extradata);
			$this->menu_extradata->reset();
			$this->setCurrentMenu(null);
			return true;
		}
		return false;
	}
}
