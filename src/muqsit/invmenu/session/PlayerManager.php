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

use pocketmine\Player;

final class PlayerManager{

	/** @var PlayerSession[] */
	private static $sessions = [];

	public static function create(Player $player) : void{
		self::$sessions[$player->getRawUniqueId()] = new PlayerSession($player);
	}

	public static function destroy(Player $player) : void{
		self::$sessions[$uuid = $player->getRawUniqueId()]->finalize();
		unset(self::$sessions[$uuid]);
	}

	public static function get(Player $player) : ?PlayerSession{
		return self::$sessions[$player->getRawUniqueId()] ?? null;
	}
}