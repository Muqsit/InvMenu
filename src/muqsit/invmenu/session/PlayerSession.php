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
use pocketmine\player\Player;

class PlayerSession{

	protected Player $player;
	protected PlayerNetwork $network;
	protected ?InvMenuInfo $current = null;

	public function __construct(Player $player, PlayerNetwork $network){
		$this->player = $player;
		$this->network = $network;
	}

	/**
	 * @internal
	 */
	public function finalize() : void{
		if($this->current !== null){
			$this->player->removeCurrentWindow();
			$this->current->graphic->remove($this->player);
		}
		$this->network->dropPending();
	}

	public function getCurrent() : ?InvMenuInfo{
		return $this->current;
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param InvMenuInfo|null $current
	 * @param Closure|null $callback
	 *
	 * @phpstan-param Closure(bool) : void $callback
	 */
	public function setCurrentMenu(?InvMenuInfo $current, ?Closure $callback = null) : void{
		$this->current = $current;

		if($this->current !== null){
			$this->network->waitUntil($this->network->getGraphicWaitDuration(), function(bool $success) use($callback) : void{
				if($this->current !== null){
					if($success && $this->current->graphic->sendInventory($this->player, $this->current->menu->getInventory())){
						if($callback !== null){
							$callback(true);
						}
						return;
					}

					$this->removeCurrentMenu();
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

	/**
	 * @internal use Player::removeCurrentWindow() instead
	 * @return bool
	 */
	public function removeCurrentMenu() : bool{
		if($this->current !== null){
			$this->current->graphic->remove($this->player);
			$this->setCurrentMenu(null);
			return true;
		}
		return false;
	}
}
