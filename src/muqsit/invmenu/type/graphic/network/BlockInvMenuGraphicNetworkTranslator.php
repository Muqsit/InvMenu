<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic\network;

use InvalidStateException;
use muqsit\invmenu\session\InvMenuInfo;
use muqsit\invmenu\session\PlayerSession;
use muqsit\invmenu\type\graphic\PositionedInvMenuGraphic;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class BlockInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void{
		$graphic = $current->graphic;
		if(!($graphic instanceof PositionedInvMenuGraphic)){
			throw new InvalidStateException("Expected " . PositionedInvMenuGraphic::class . ", got " . get_class($graphic));
		}

		$pos = $graphic->getPosition();
		$packet->x = (int) $pos->x;
		$packet->y = (int) $pos->y;
		$packet->z = (int) $pos->z;
	}
}