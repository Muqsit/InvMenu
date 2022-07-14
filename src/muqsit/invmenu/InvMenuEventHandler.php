<?php

declare(strict_types=1);

namespace muqsit\invmenu;

use muqsit\invmenu\session\network\PlayerNetwork;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

final class InvMenuEventHandler implements Listener{
	
	public function __construct(
		private PlayerManager $player_manager
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
				$this->player_manager->getNullable($player)?->getNetwork()->notify($packet->timestamp);
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

		$current = $session->getCurrent();
		if($current !== null && $event->getInventory() === $current->menu->getInventory()){
			$current->menu->onClose($player);
		}
		$session->getNetwork()->waitUntil(PlayerNetwork::DELAY_TYPE_ANIMATION_WAIT, 325, static fn(bool $success) : bool => false);
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority NORMAL
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();

		$player_instance = $this->player_manager->get($player);
		$current = $player_instance->getCurrent();
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
			$network_stack_callback = $result->getPostTransactionCallback();
			if($network_stack_callback !== null){
				$network_stack_callbacks[] = $network_stack_callback;
			}
			if($result->isCancelled()){
				$event->cancel();
				break;
			}
		}

		if(count($network_stack_callbacks) > 0){
			$player_instance->getNetwork()->wait(PlayerNetwork::DELAY_TYPE_ANIMATION_WAIT, static function(bool $success) use($player, $network_stack_callbacks) : bool{
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
