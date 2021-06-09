<?php

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