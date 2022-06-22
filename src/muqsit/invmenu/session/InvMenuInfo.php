<?php

declare(strict_types=1);

namespace muqsit\invmenu\session;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		public InvMenu $menu,
		public InvMenuGraphic $graphic,
		public ?string $graphic_name
	){}
}