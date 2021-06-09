<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\util\builder;

use muqsit\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}