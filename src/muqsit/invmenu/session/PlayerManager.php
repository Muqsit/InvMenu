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

use muqsit\invmenu\session\network\handler\PlayerNetworkHandlerRegistry;
use muqsit\invmenu\session\network\PlayerNetwork;
use pocketmine\player\Player;
use ReflectionProperty;

final class PlayerManager{

	/** @var PlayerSession[] */
	private static array $sessions = [];

	public static function create(Player $player) : void{
		static $_playerInfo = null;
		if($_playerInfo === null){
			$_playerInfo = new ReflectionProperty(Player::class, "playerInfo");
			$_playerInfo->setAccessible(true);
		}

		self::$sessions[$player->getId()] = new PlayerSession(
			$player,
			new PlayerNetwork(
				$player->getNetworkSession(),
				PlayerNetworkHandlerRegistry::get($_playerInfo->getValue($player)->getExtraData()["DeviceOS"] ?? -1)
			)
		);
	}

	public static function destroy(Player $player) : void{
		if(isset(self::$sessions[$player_id = $player->getId()])){
			self::$sessions[$player_id]->finalize();
			unset(self::$sessions[$player_id]);
		}
	}

	public static function get(Player $player) : ?PlayerSession{
		return self::$sessions[$player->getId()] ?? null;
	}

	public static function getNonNullable(Player $player) : PlayerSession{
		return self::$sessions[$player->getId()];
	}
}