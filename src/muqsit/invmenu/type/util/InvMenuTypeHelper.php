<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util;

use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class InvMenuTypeHelper{

	public const NETWORK_WORLD_Y_MIN = -64;
	public const NETWORK_WORLD_Y_MAX = 320;

	public static function getBehindPositionOffset(Player $player) : Vector3{
		$offset = $player->getDirectionVector();
		$size = $player->size;
		$offset->x *= -(1 + $size->getWidth());
		$offset->y *= -(1 + $size->getHeight());
		$offset->z *= -(1 + $size->getWidth());
		return $offset;
	}

	public static function isValidYCoordinate(float $y) : bool{
		return $y >= self::NETWORK_WORLD_Y_MIN && $y <= self::NETWORK_WORLD_Y_MAX;
	}
}