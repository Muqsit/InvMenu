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

namespace muqsit\invmenu;

use muqsit\invmenu\inventories\ChestInventory;
use muqsit\invmenu\inventories\DoubleChestInventory;
use muqsit\invmenu\inventories\HopperInventory;

interface MenuIds{

	// This interface exists for backwards compatibility.

	const TYPE_CHEST = ChestInventory::class;
	const TYPE_DOUBLE_CHEST = DoubleChestInventory::class;
	const TYPE_HOPPER = HopperInventory::class;
}