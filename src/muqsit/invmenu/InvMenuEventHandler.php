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

namespace muqsit\invmenu;

use muqsit\invmenu\session\PlayerManager;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class InvMenuEventHandler implements Listener{

	/**
	 * @param PlayerLoginEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		PlayerManager::create($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		PlayerManager::destroy($event->getPlayer());
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @priority NORMAL
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof NetworkStackLatencyPacket){
			$session = PlayerManager::get($event->getOrigin()->getPlayer());
			if($session !== null){
				$session->getNetwork()->notify($packet->timestamp);
			}
		}
	}

	/**
	 * @param DataPacketSendEvent $event
	 * @priority NORMAL
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packets = $event->getPackets();
		if(count($packets) === 1){
			$packet = reset($packets);
			if($packet instanceof ContainerOpenPacket){
				$targets = $event->getTargets();
				if(count($targets) === 1){
					$target = reset($targets);
					$session = PlayerManager::get($target->getPlayer());
					if($session !== null && $session->getNetwork()->translateContainerOpen($session, $packet->windowId, $packet->type, $packet->x, $packet->y, $packet->z)){
						$event->cancel();
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
		$session = PlayerManager::get($player);
		if($session !== null){
			$menu = $session->getCurrentMenu();
			if($menu !== null && $event->getInventory() === $menu->getInventory()){
				$menu->onClose($player);
			}
		}
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority NORMAL
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();

		$player_instance = PlayerManager::getNonNullable($player);
		$menu = $player_instance->getCurrentMenu();
		if($menu !== null){
			$inventory = $menu->getInventory();
			$network_stack_callbacks = [];
			foreach($transaction->getActions() as $action){
				if($action instanceof SlotChangeAction && $action->getInventory() === $inventory){
					$result = $menu->handleInventoryTransaction($player, $action->getSourceItem(), $action->getTargetItem(), $action, $transaction);
					$network_stack_callback = $result->getPostTransactionCallback();
					if($network_stack_callback !== null){
						$network_stack_callbacks[] = $network_stack_callback;
					}
					if($result->isCancelled()){
						$event->cancel();
						break;
					}
				}
			}
			if(count($network_stack_callbacks) > 0){
				$player_instance->getNetwork()->wait(static function(bool $success) use($player, $network_stack_callbacks) : void{
					if($success){
						foreach($network_stack_callbacks as $callback){
							$callback($player);
						}
					}
				});
			}
		}
	}
}