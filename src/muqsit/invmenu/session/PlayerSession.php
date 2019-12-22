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

	public function __construct(Player $player){
		$this->player = $player;
		$this->network = new PlayerNetwork($player->getNetworkSession());
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
	 * @param Closure|null $callback
	 * @return bool
	 */
	public function setCurrentMenu(?InvMenu $menu, ?Closure $callback = null) : bool{
		if($menu !== null){
			$this->network->wait(function(bool $success) use($callback) : void{
				if($success && $this->current_menu !== null){
					if($this->current_menu->sendInventory($this->player)){
						// TODO: Revert this to the Inventory->moveTo() method when it's possible
						// for plugins to specify network type for inventories
						if($this->player->getNetworkSession()->sendDataPacket(ContainerOpenPacket::blockInvVec3(
							$this->player->getNetworkSession()->getInvManager()->getCurrentWindowId(),
							$this->current_menu->getType()->getWindowType(),
							$this->menu_extradata->getPosition()
						))){
							$this->player->getNetworkSession()->getInvManager()->syncContents($this->current_menu->getInventoryForPlayer($this->player));
							if($callback !== null){
								$callback(true);
							}
							return;
						}
					}else{
						$this->setCurrentMenu(null);
					}
				}
				if($callback !== null){
					$callback(true);
				}
			});
		}

		$this->current_menu = $menu;
		return true;
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
		return $this->setCurrentMenu(null);
	}
}
