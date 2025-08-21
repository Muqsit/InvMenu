<?php

declare(strict_types=1);

namespace muqsit\invmenu;

use muqsit\invmenu\session\network\PlayerNetwork;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\session\PlayerWindowDispatcher;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;

final class InvMenuEventHandler implements Listener{
	
	public function __construct(
		readonly private PlayerManager $player_manager
	){}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @priority NORMAL
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof NetworkStackLatencyPacket){
			$player = $event->getOrigin()->getPlayer();
			if($player !== null){
				$this->player_manager->getNullable($player)?->network->notify($packet->timestamp);
			}
		}elseif($packet instanceof ContainerClosePacket){
			// these are not magic numbers. 255 (windowId) is supposed to be ContainerIds::NONE (-1) but it appears
			// either pocketmine or mojang wrongly encodes/decodes the packet. the same applies to 247 (windowType)
			// which actually is WindowTypes::NONE (-9).
			if(!$packet->server && $packet->windowId === 255 && $packet->windowType === 247){
				$player = $event->getOrigin()->getPlayer();
				if($player !== null && $this->player_manager->getNullable($player)?->dispatcher !== null){
					$event->cancel();
				}
			}
		}elseif($packet instanceof PacketViolationWarningPacket){
			// we (ab)use a packet violation as an ACK the inventory was successfully sent to the player. we expect to
			// receive the same number of violation packets as the number of excess ContainerOpenPackets that we sent.
			// digesting these excess violation packets is not necessary, but in this way we can intercept violations
			// from propagating further if existing plugins print these violations for debugging purposes.
			if($packet->getPacketId() === PacketViolationWarningPacket::NETWORK_ID && $packet->getType() === -1 && $packet->getSeverity() === PacketViolationWarningPacket::SEVERITY_WARNING){
				$player = $event->getOrigin()->getPlayer();
				if($player !== null){
					$dispatcher = $this->player_manager->getNullable($player)?->dispatcher;
					if($dispatcher !== null){
						if($dispatcher->state === PlayerWindowDispatcher::STATE_SENDING){
							$dispatcher->setResult(true);
							$event->cancel();
						}elseif($dispatcher->state === PlayerWindowDispatcher::STATE_FINALIZING){
							if(--$dispatcher->n_finalization_acks <= 0){
								$dispatcher->finalize();
							}
							$event->cancel();
						}
					}
				}
			}
		}
	}

	/**
	 * @param InventoryCloseEvent $event
	 * @priority MONITOR
	 */
	public function onInventoryClose(InventoryCloseEvent $event) : void{
		$player = $event->getPlayer();
		$session = $this->player_manager->getNullable($player);
		if($session === null){
			return;
		}

		$current = $session->current;
		if($current !== null && $event->getInventory() === $current->menu->getInventory()){
			$current->graphic->remove($player);
			$session->current = null;
		}
		$session->network->wait(PlayerNetwork::DELAY_TYPE_ANIMATION_WAIT, static fn($success) => false);
		if($session->dispatcher !== null && $session->dispatcher->state === PlayerWindowDispatcher::STATE_SENDING && $session->dispatcher->info === $current){
			return;
		}
		$current?->menu->onClose($player);
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority NORMAL
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();

		$player_instance = $this->player_manager->get($player);

		// cancel transaction if menu is still being sent
		if($player_instance->dispatcher !== null && $player_instance->dispatcher->state !== PlayerWindowDispatcher::STATE_FINALIZING){
			$inventory = $player_instance->dispatcher->info->menu->getInventory();
			foreach($transaction->getActions() as $action){
				if($action instanceof SlotChangeAction && $action->getInventory() === $inventory){
					$event->cancel();
					return;
				}
			}
		}

		$current = $player_instance->current;
		if($current === null){
			return;
		}

		$inventory = $current->menu->getInventory();
		$network_stack_callbacks = [];
		foreach($transaction->getActions() as $action){
			if(!($action instanceof SlotChangeAction) || $action->getInventory() !== $inventory){
				continue;
			}

			$result = $current->menu->handleInventoryTransaction($player, $action->getSourceItem(), $action->getTargetItem(), $action, $transaction);
			$network_stack_callback = $result->post_transaction_callback;
			if($network_stack_callback !== null){
				$network_stack_callbacks[] = $network_stack_callback;
			}
			if($result->cancelled){
				$event->cancel();
				break;
			}
		}

		if(count($network_stack_callbacks) > 0){
			$player_instance->network->wait(PlayerNetwork::DELAY_TYPE_ANIMATION_WAIT, static function(bool $success) use($player, $network_stack_callbacks) : bool{
				if($success){
					foreach($network_stack_callbacks as $callback){
						$callback($player);
					}
				}
				return false;
			});
		}
	}
}
