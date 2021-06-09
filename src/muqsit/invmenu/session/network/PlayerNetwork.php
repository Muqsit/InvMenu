<?php

declare(strict_types=1);

namespace muqsit\invmenu\session\network;

use Closure;
use InvalidArgumentException;
use muqsit\invmenu\session\network\handler\PlayerNetworkHandler;
use muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use SplQueue;

final class PlayerNetwork{

	private NetworkSession $session;
	private PlayerNetworkHandler $handler;
	private ?NetworkStackLatencyEntry $current = null;
	private int $graphic_wait_duration = 200;

	/** @var SplQueue<NetworkStackLatencyEntry> */
	private SplQueue $queue;

	public function __construct(NetworkSession $session, PlayerNetworkHandler $handler){
		$this->session = $session;
		$this->handler = $handler;
		$this->queue = new SplQueue();
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
		$this->queue = new SplQueue();
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
			$this->queue->enqueue($entry);
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
				$this->setCurrent($this->queue->dequeue());
			}
		}
	}

	public function notify(int $timestamp) : void{
		if($this->current !== null && $timestamp === $this->current->timestamp){
			$this->processCurrent(true);
		}
	}

	public function translateContainerOpen(PlayerSession $session, ContainerOpenPacket $packet) : bool{
		$inventory = $this->session->getInvManager()->getWindow($packet->windowId);
		if(
			$inventory !== null &&
			($current = $session->getCurrent()) !== null &&
			$current->menu->getInventory() === $inventory &&
			($translation = $current->graphic->getNetworkTranslator()) !== null
		){
			$translation->translate($session, $current, $packet);
			return true;
		}
		return false;
	}
}