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

namespace muqsit\invmenu\session\network\handler;

use Closure;
use muqsit\invmenu\session\network\NetworkStackLatencyEntry;
use pocketmine\network\mcpe\protocol\types\DeviceOS;

final class PlayerNetworkHandlerRegistry{

	/** @var PlayerNetworkHandler */
	private static $default;

	/** @var PlayerNetworkHandler[] */
	private static $game_os_handlers = [];

	public static function init() : void{
		self::registerDefault(new ClosurePlayerNetworkHandler(static function(Closure $then) : NetworkStackLatencyEntry{
			return new NetworkStackLatencyEntry(mt_rand() * 1000 /* TODO: remove this hack */, $then);
		}));
		self::register(DeviceOS::PLAYSTATION, new ClosurePlayerNetworkHandler(static function(Closure $then) : NetworkStackLatencyEntry{
			$timestamp = mt_rand();
			return new NetworkStackLatencyEntry($timestamp, $then, $timestamp * 1000);
		}));
	}

	public static function registerDefault(PlayerNetworkHandler $handler) : void{
		self::$default = $handler;
	}

	public static function register(int $os_id, PlayerNetworkHandler $handler) : void{
		self::$game_os_handlers[$os_id] = $handler;
	}

	public static function get(int $os_id) : PlayerNetworkHandler{
		return self::$game_os_handlers[$os_id] ?? self::$default;
	}
}