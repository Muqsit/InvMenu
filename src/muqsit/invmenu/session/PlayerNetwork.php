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
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;

final class PlayerNetwork{

	/** @var Player */
	private $player;

	/** @var Closure[] */
	private $awaiting = [];

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function dropPending() : void{
		foreach($this->awaiting as $callback){
			$callback(false);
		}
		$this->awaiting = [];
	}

	public function wait(Closure $then) : void{
		$timestamp = mt_rand() * 1000; // TODO: remove this hack when fixed
		$this->awaiting[$timestamp] = $then;
		if(count($this->awaiting) === 1 && !$this->sendTimestamp($timestamp)){
			$this->notify($timestamp, false);
		}
	}

	public function notify(int $timestamp, bool $success = true) : void{
		if(isset($this->awaiting[$timestamp])){
			$this->awaiting[$timestamp]($success);
			unset($this->awaiting[$timestamp]);
			if(count($this->awaiting) > 0){
				$this->sendTimestamp(array_keys($this->awaiting)[0]);
			}
		}
	}

	private function sendTimestamp(int $timestamp) : bool{
		$pk = new NetworkStackLatencyPacket();
		$pk->timestamp = $timestamp;
		$pk->needResponse = true;
		return $this->player->sendDataPacket($pk);
	}
}