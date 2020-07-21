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
use Ds\Queue;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

final class PlayerNetwork{

	/** @var NetworkSession */
	private $session;

	/** @var Queue<NetworkStackLatencyEntry> */
	private $queue;

	/** @var NetworkStackLatencyEntry|null */
	private $current;

	public function __construct(NetworkSession $session){
		$this->session = $session;
		$this->queue = new Queue();
	}

	public function dropPending() : void{
		foreach($this->queue as $entry){
			($entry->then)(false);
		}
		$this->queue->clear();
		$this->setCurrent(null);
	}

	/**
	 * @param Closure $then
	 *
	 * @phpstan-param Closure(bool) : void $then
	 */
	public function wait(Closure $then) : void{
		$entry = new NetworkStackLatencyEntry(mt_rand() * 1000 /* TODO: remove this hack */, $then);
		if($this->current !== null){
			$this->queue->push($entry);
		}else{
			$this->setCurrent($entry);
		}
	}

	private function setCurrent(?NetworkStackLatencyEntry $entry) : void{
		if($entry !== null){
			$pk = new NetworkStackLatencyPacket();
			$pk->timestamp = $entry->timestamp;
			$pk->needResponse = true;
			if(!$this->session->sendDataPacket($pk)){
				($entry->then)(false);
			}
		}else{
			if($this->current !== null){
				$this->processCurrent(false);
			}
		}

		$this->current = $entry;
	}

	private function processCurrent(bool $success) : void{
		if($this->current !== null){
			($this->current->then)($success);
			$this->current = null;
			if(!$this->queue->isEmpty()){
				$this->setCurrent($this->queue->pop());
			}
		}
	}

	public function notify(int $timestamp) : void{
		if($this->current !== null && $timestamp === $this->current->timestamp){
			$this->processCurrent(true);
		}
	}
}