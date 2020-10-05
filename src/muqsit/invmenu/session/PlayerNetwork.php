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
use InvalidArgumentException;
use muqsit\invmenu\session\network\handler\PlayerNetworkHandler;
use muqsit\invmenu\session\network\NetworkStackLatencyEntry;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;

final class PlayerNetwork{

	/** @var Player */
	private $session;

	/** @var Queue<NetworkStackLatencyEntry> */
	private $queue;

	/** @var NetworkStackLatencyEntry|null */
	private $current;

	/** @var PlayerNetworkHandler */
	private $handler;

	/** @var int */
	private $graphic_wait_duration = 50 * 5;

	public function __construct(Player $session, PlayerNetworkHandler $handler){
		$this->session = $session;
		$this->handler = $handler;
		$this->queue = new Queue();
	}

	public function getGraphicWaitDuration() : int{
		return $this->graphic_wait_duration;
	}

	/**
	 * Duration (in milliseconds) to wait between sending the graphic (block)
	 * and sending the inventory.
	 *
	 * @param int $graphic_wait_duration
	 */
	public function setGraphicWaitDuration(int $graphic_wait_duration) : void{
		if($graphic_wait_duration < 0){
			throw new InvalidArgumentException("graphic_wait_duration must be >= 0, got {$graphic_wait_duration}");
		}

		$this->graphic_wait_duration = $graphic_wait_duration;
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
		$entry = $this->handler->createNetworkStackLatencyEntry($then);
		if($this->current !== null){
			$this->queue->push($entry);
		}else{
			$this->setCurrent($entry);
		}
	}

	/**
	 * Waits at least $wait_ms before calling $then(true).
	 *
	 * @param int $wait_ms
	 * @param Closure $then
	 * @param int|null $since_ms
	 *
	 * @phpstan-param Closure(bool) : void $then
	 */
	public function waitUntil(int $wait_ms, Closure $then, ?int $since_ms = null) : void{
		if($since_ms === null){
			$since_ms = (int) floor(microtime(true) * 1000);
		}
		$this->wait(function(bool $success) use($since_ms, $wait_ms, $then) : void{
			if($success && ((microtime(true) * 1000) - $since_ms) < $wait_ms){
				$this->waitUntil($wait_ms, $then, $since_ms);
			}else{
				$then($success);
			}
		});
	}

	private function setCurrent(?NetworkStackLatencyEntry $entry) : void{
		if($this->current !== null){
			$this->processCurrent(false);
			$this->current = null;
		}

		if($entry !== null){
			$pk = new NetworkStackLatencyPacket();
			$pk->timestamp = $entry->network_timestamp;
			$pk->needResponse = true;
			if($this->session->sendDataPacket($pk)){
				$this->current = $entry;
			}else{
				($entry->then)(false);
			}
		}
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