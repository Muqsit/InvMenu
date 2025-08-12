<?php

declare(strict_types=1);

namespace muqsit\invmenu\session;

use BadMethodCallException;
use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\network\PlayerNetwork;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use function assert;

final class PlayerWindowDispatcher{

	public const STATE_SENDING = 0;
	public const STATE_FINALIZING = 1;
	public const STATE_COMPLETED = 2;

	private ?TaskHandler $task_handler = null;
	private ?Closure $container_open_callback = null;

	private ?int $window_id = null;

	/** @var list<ClientboundPacket> */
	private array $packets;

	/** @var list<Closure(bool) : void> */
	private array $callbacks = [];

	public ?bool $result = null;
	public int $n_finalization_acks = 0;
	public int $state = self::STATE_SENDING;

	/** @var array{InvMenu, string|null, Closure|null}|null */
	public ?array $after_finalization = null;

	public function __construct(
		readonly public PlayerSession $session,
		readonly public InvMenuInfo $info,
		public int $retry_timeo = 20, // ticks
		public int $finalization_timeo = 20 // ticks
	){
		$info->graphic->send($session->player, $info->graphic_name);
		$session->network->waitUntil(PlayerNetwork::DELAY_TYPE_OPERATION, $info->graphic->getAnimationDuration(), function(bool $success) : void{
			$success = $success && $this->registerContainerOpenCallbacks();
			$success = $success && $this->info->graphic->sendInventory($this->session->player, $this->info->menu->getInventory());
			if($success){
				$this->session->current = $this->info;
			}else{
				$this->setResult(false);
			}
		});
	}

	private function registerContainerOpenCallbacks() : bool{
		$translator = $this->info->graphic->getNetworkTranslator();
		$callbacks = $this->session->player->getNetworkSession()->getInvManager()?->getContainerOpenCallbacks();
		if($translator === null || $callbacks === null){
			return false;
		}
		$previous = $callbacks->toArray();
		$this->container_open_callback = function(int $window_id, Inventory $inventory) use($translator, $callbacks, $previous) : ?array{
			if($inventory !== $this->info->menu->getInventory()){
				return null;
			}

			$callbacks->remove($this->container_open_callback);
			$this->container_open_callback = null;

			$packets = null;
			foreach($previous as $callback){
				$packets = $callback($window_id, $inventory);
				if($packets !== null){
					break;
				}
			}

			$packets ??= [ContainerOpenPacket::blockInv($window_id, WindowTypes::CONTAINER, $inventory instanceof BlockInventory ?
				BlockPosition::fromVector3($inventory->getHolder()) :
				new BlockPosition(0, 0, 0)
			)];

			foreach($packets as $packet){
				if($packet instanceof ContainerOpenPacket){
					$translator->translate($this->session, $this->info, $packet);
				}
			}

			$this->window_id = $window_id;
			$this->packets = $packets;
			$this->task_handler = InvMenuHandler::getRegistrant()->getScheduler()->scheduleRepeatingTask(new ClosureTask($this->run(...)), 1);
			return $packets;
		};
		// Take priority over other container open callbacks.
		// PocketMine's default container open callback disallows any BlockInventory
		// from having a custom callback
		$callbacks->clear();
		$callbacks->add($this->container_open_callback, ...$previous);
		return true;
	}

	public function run() : void{
		if(--$this->retry_timeo < 0 || !$this->session->player->isConnected()){
			$this->setResult(false);
			return;
		}

		assert($this->window_id !== null);
		$session = $this->session->player->getNetworkSession();
		$session->sendDataPacket(ContainerClosePacket::create($this->window_id, WindowTypes::CONTAINER, false));
		$this->n_finalization_acks++;
		foreach($this->packets as $packet){
			$session->sendDataPacket($packet);
		}
	}

	public function setResult(bool $result) : void{
		$this->result = $result;
		if(!$result){
			// this belongs here so we do not end up calling inventory close listener on failed dispatches
			$this->session->current = null;
		}
		$this->task_handler?->cancel();
		$this->task_handler = null;
		if($this->session->player->isConnected()){
			$manager = $this->session->player->getNetworkSession()->getInvManager();
			if($this->container_open_callback !== null){
				$manager?->getContainerOpenCallbacks()->remove($this->container_open_callback);
			}
			if($result){
				$this->session->player->getNetworkSession()->getInvManager()?->syncContents($this->info->menu->getInventory());
			}else{
				if($this->window_id !== null){
					$this->session->player->removeCurrentWindow();
					$manager?->onClientRemoveWindow($this->window_id); // for 'dirty' sends - when we send container but wait for graphic
				}
				$this->info->graphic->remove($this->session->player);
			}
		}
		$this->container_open_callback = null;
		foreach($this->callbacks as $callback){
			$callback($result);
		}
		$this->callbacks = [];
		$this->state = self::STATE_FINALIZING;
		if($this->n_finalization_acks > 0){
			$this->task_handler = InvMenuHandler::getRegistrant()->getScheduler()->scheduleDelayedTask(new ClosureTask($this->finalize(...)), $this->finalization_timeo);
		}else{
			$this->finalize();
		}
	}

	/**
	 * @param Closure(bool) : void $callback
	 */
	public function addCallback(Closure $callback) : void{
		if($this->result !== null){
			$callback($this->result);
		}else{
			$this->callbacks[] = $callback;
		}
	}

	public function finalize() : void{
		if($this->result === null){
			$this->setResult(false);
		}
		$this->task_handler?->cancel();
		$this->task_handler = null;
		$this->state = self::STATE_COMPLETED;
		$this->n_finalization_acks = 0;
		if($this->session->dispatcher === $this){
			$this->session->dispatcher = null;
		}
		if($this->after_finalization !== null){
			if($this->session->player->isConnected()){
				[$menu, $name, $cb] = $this->after_finalization;
				$menu->send($this->session->player, $name, $cb);
			}elseif($this->after_finalization[2] !== null){
				$this->after_finalization[2](false);
			}
		}
		$this->after_finalization = null;
	}

	public function then(InvMenu $menu, ?string $name, ?Closure $callback) : void{
		$this->state !== self::STATE_COMPLETED || throw new BadMethodCallException("No need to call then() on completed dispatcher");
		if($this->after_finalization !== null && $this->after_finalization[2] !== null){
			$this->after_finalization[2](false);
		}
		$this->after_finalization = [$menu, $name, $callback];
	}
}
