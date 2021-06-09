<?php

declare(strict_types=1);

namespace muqsit\invmenu\type\graphic\network;

use muqsit\invmenu\session\InvMenuInfo;
use muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class MultiInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator{

	/** @var InvMenuGraphicNetworkTranslator[] */
	private array $translators;

	/**
	 * @param InvMenuGraphicNetworkTranslator[] $translators
	 */
	public function __construct(array $translators){
		$this->translators = $translators;
	}

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void{
		foreach($this->translators as $translator){
			$translator->translate($session, $current, $packet);
		}
	}
}