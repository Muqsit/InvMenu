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
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\UUID;

class InvMenuEventHandler implements Listener{

	/** @var int[] */
	private static $cached_device_os = [];

	public static function pullCachedDeviceOS(Player $player) : int{
		if(isset(self::$cached_device_os[$uuid = $player->getRawUniqueId()])){
			$device_os = self::$cached_device_os[$uuid];
			unset(self::$cached_device_os[$uuid]);
			return $device_os;
		}

		return -1;
	}

	public function __construct(Plugin $plugin){
		$server = $plugin->getServer();
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function(int $currentTick) use($server) : void{
			foreach(self::$cached_device_os as $uuid => $_){
				if($server->getPlayerByRawUUID($uuid) === null){
					unset(self::$cached_device_os[$uuid]);
				}
			}
		}), 100);
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority LOWEST
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
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
	 * @ignoreCancelled true
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof NetworkStackLatencyPacket){
			$session = PlayerManager::get($event->getPlayer());
			if($session !== null){
				$session->getNetwork()->notify($packet->timestamp);
			}
		}elseif($packet instanceof LoginPacket){
			self::$cached_device_os[UUID::fromString($packet->clientUUID)->toBinary()] = $packet->clientData["DeviceOS"] ?? -1;
		}
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
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
						$event->setCancelled();
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