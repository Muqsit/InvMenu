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

namespace muqsit\invmenu\session;

use Closure;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

final class PlayerNetwork{

	/** @var NetworkSession */
	private $session;

	/** @var Closure[] */
	private $awaiting = [];

	public function __construct(NetworkSession $session){
		$this->session = $session;
	}

	public function dropPending() : void{
		foreach($this->awaiting as $callback){
			$callback(false);
		}
		$this->awaiting = [];
	}

	/**
	 * @param Closure $then
	 *
	 * @phpstan-param Closure(bool) : void $then
	 */
	public function wait(Closure $then) : void{
		$timestamp = mt_rand() * 1000; // TODO: remove this hack when fixed

		$pk = new NetworkStackLatencyPacket();
		$pk->timestamp = $timestamp;
		$pk->needResponse = true;

		if($this->session->sendDataPacket($pk)){
			$this->awaiting[$timestamp] = $then;
		}else{
			$then(false);
		}
	}

	public function notify(int $timestamp) : void{
		if(isset($this->awaiting[$timestamp])){
			$this->awaiting[$timestamp](true);
			unset($this->awaiting[$timestamp]);
		}
	}
}