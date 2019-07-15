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

class DoubleBlockMenuMetadata extends SingleBlockMenuMetadata{

	protected function getBlockActorDataAt(Vector3 $pos, ?string $name) : CompoundTag{
		return parent::getBlockActorDataAt($pos, $name)
			->setInt("pairx", $pos->x + (($pos->x & 1) ? 1 : -1))
			->setInt("pairz", $pos->z);
	}

	protected function getBlockPositions(MenuExtradata $metadata) : array{
		$pos = $metadata->getPosition();
		return [$pos, ($pos->x & 1) ? $pos->east() : $pos->west()];
	}
}