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
use InvalidArgumentException;
use InvalidStateException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\Player;

class PlayerSession{

	private const INVMENU_FORCE_ID_MIN = 83;
	private const INVMENU_FORCE_ID_MAX = 84;

	/** @var Player */
	protected $player;

	/** @var PlayerNetwork */
	protected $network;

	/** @var MenuExtradata */
	protected $menu_extradata;

	/** @var InvMenu|null */
	protected $current_menu;

	/** @var int */
	protected $last_force_id = self::INVMENU_FORCE_ID_MIN;

	public function __construct(Player $player){
		$this->player = $player;
		$this->network = new PlayerNetwork($player);
		$this->menu_extradata = new MenuExtradata();
	}

	/**
	 * @internal
	 */
	public function finalize() : void{
		if($this->current_menu !== null){
			$this->removeWindow();
		}
	}

	public function removeWindow() : void{
		for($i = self::INVMENU_FORCE_ID_MIN; $i <= self::INVMENU_FORCE_ID_MAX; ++$i){
			$window = $this->player->getWindow($i);
			if($window !== null){
				$this->player->removeWindow($window);
			}
		}
	}

	private function getForceId() : int{
		if($this->last_force_id > self::INVMENU_FORCE_ID_MAX){
			$this->last_force_id = self::INVMENU_FORCE_ID_MIN;
		}
		return $this->last_force_id++;
	}

	private function sendWindow() : bool{
		$windowId = null;
		try{
			$position = $this->menu_extradata->getPosition();
			$inventory = $this->current_menu->getInventoryForPlayer($this->player);
			/** @noinspection NullPointerExceptionInspection */
			$inventory->moveTo($position->x, $position->y, $position->z);
			$windowId = $this->player->addWindow($inventory, $this->getForceId());
		}catch(InvalidStateException | InvalidArgumentException $e){
			InvMenuHandler::getRegistrant()->getLogger()->debug("InvMenu failed to send inventory to " . $this->player->getName() . " due to: " . $e->getMessage());
		}
		return $windowId >= self::INVMENU_FORCE_ID_MIN && $windowId <= self::INVMENU_FORCE_ID_MAX;
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
					if($this->sendWindow()){
						if($callback !== null){
							$callback(true);
						}
						return;
					}
					$this->setCurrentMenu(null);
				}
				if($callback !== null){
					$callback(false);
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
	 * @internal use PlayerSession::removeWindow() instead
	 * @return bool
	 */
	public function removeCurrentMenu() : bool{
		return $this->setCurrentMenu(null);
	}
}
