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

namespace muqsit\invmenu\metadata;

use muqsit\invmenu\session\MenuExtradata;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

class DoubleBlockActorMenuMetadata extends SingleBlockActorMenuMetadata{

	protected function getBlockActorDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		return parent::getBlockActorDataAt($pos, $name)
			->setInt("pairx", (int) ($pos->x + (($pos->x & 1) ? 1 : -1)))
			->setInt("pairz", (int) $pos->z);
	}

	protected function getBlockPositions(MenuExtradata $metadata) : array{
		$pos = $metadata->getPositionNotNull();
		return $pos->y >= 0 && $pos->y < World::Y_MAX ? [$pos, ($pos->x & 1) ? $pos->east() : $pos->west()] : [];
	}

	protected function calculateGraphicOffset(Player $player) : Vector3{
		$offset = parent::calculateGraphicOffset($player);
		$offset->x *= 2;
		$offset->z *= 2;
		return $offset;
	}
}