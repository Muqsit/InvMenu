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
use function spl_object_id;

final class PlayerNetwork{

	public const DELAY_TYPE_ANIMATION_WAIT = 0;
	public const DELAY_TYPE_OPERATION = 1;

	private ?NetworkStackLatencyEntry $current = null;
	private int $graphic_wait_duration = 200;

	/** @var SplQueue<NetworkStackLatencyEntry> */
	private SplQueue $queue;

	/** @var array<int, self::DELAY_TYPE_*> */
	private array $entry_types = [];

	public function __construct(
		private NetworkSession $session,
		private PlayerNetworkHandler $handler
	){
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

	public function getPending() : int{
		return $this->queue->count();
	}

	public function dropPending() : void{
		foreach($this->queue as $entry){
			($entry->then)(false);
		}
		$this->queue = new SplQueue();
		$this->entry_types = [];
		$this->setCurrent(null);
	}

	/**
	 * @param self::DELAY_TYPE_* $type
	 */
	public function dropPendingOfType(int $type) : void{
		$previous = $this->queue;
		$this->queue = new SplQueue();
		foreach($previous as $entry){
			if($this->entry_types[$id = spl_object_id($entry)] === $type){
				($entry->then)(false);
				unset($this->entry_types[$id]);
			}else{
				$this->queue->enqueue($entry);
			}
		}
	}

	/**
	 * @param self::DELAY_TYPE_* $type
	 * @param Closure(bool) : bool $then
	 */
	public function wait(int $type, Closure $then) : void{
		$entry = $this->handler->createNetworkStackLatencyEntry($then);
		if($this->current !== null){
			$this->queue->enqueue($entry);
			$this->entry_types[spl_object_id($entry)] = $type;
		}else{
			$this->setCurrent($entry);
		}
	}

	/**
	 * Waits at least $wait_ms before calling $then(true).
	 *
	 * @param self::DELAY_TYPE_* $type
	 * @param int $wait_ms
	 * @param Closure(bool) : bool $then
	 */
	public function waitUntil(int $type, int $wait_ms, Closure $then) : void{
		if($wait_ms <= 0 && $this->queue->isEmpty()){
			$then(true);
			return;
		}

		$elapsed_ms = 0.0;
		$this->wait($type, function(bool $success) use($wait_ms, $then, &$elapsed_ms) : bool{
			if($this->current === null){
				$then(false);
				return false;
			}

			$elapsed_ms += (microtime(true) * 1000) - $this->current->sent_at;
			if(!$success || $elapsed_ms >= $wait_ms){
				$then($success);
				return false;
			}

			return true;
		});
	}

	private function setCurrent(?NetworkStackLatencyEntry $entry) : void{
		if($this->current !== null){
			$this->processCurrent(false);
		}

		$this->current = $entry;
		if($entry !== null){
			unset($this->entry_types[spl_object_id($entry)]);
			if($this->session->sendDataPacket(NetworkStackLatencyPacket::create($entry->network_timestamp, true))){
				$entry->sent_at = microtime(true) * 1000;
			}else{
				$this->processCurrent(false);
			}
		}
	}

	private function processCurrent(bool $success) : void{
		if($this->current !== null){
			$current = $this->current;
			$repeat = ($current->then)($success);
			$this->current = null;
			if($repeat && $success){
				$this->setCurrent($current);
			}elseif(!$this->queue->isEmpty()){
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
		$inventory = $this->session->getInvManager()?->getWindow($packet->windowId);
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