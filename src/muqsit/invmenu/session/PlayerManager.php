<?php

declare(strict_types=1);

namespace muqsit\invmenu\session;

use muqsit\invmenu\session\network\handler\PlayerNetworkHandlerRegistry;
use muqsit\invmenu\session\network\PlayerNetwork;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use ReflectionProperty;

final class PlayerManager{

	private PlayerNetworkHandlerRegistry $network_handler_registry;

	/** @var PlayerSession[] */
	private array $sessions = [];
	
	public function __construct(Plugin $registrant){
		$this->network_handler_registry = new PlayerNetworkHandlerRegistry();

		$plugin_manager = Server::getInstance()->getPluginManager();
		$plugin_manager->registerEvent(PlayerLoginEvent::class, function(PlayerLoginEvent $event) : void{
			$this->create($event->getPlayer());
		}, EventPriority::MONITOR, $registrant);
		$plugin_manager->registerEvent(PlayerQuitEvent::class, function(PlayerQuitEvent $event) : void{
			$this->destroy($event->getPlayer());
		}, EventPriority::MONITOR, $registrant);
	}

	private function create(Player $player) : void{
		static $_playerInfo = null;
		if($_playerInfo === null){
			$_playerInfo = new ReflectionProperty(Player::class, "playerInfo");
			$_playerInfo->setAccessible(true);
		}

		$this->sessions[$player->getId()] = new PlayerSession($player, new PlayerNetwork(
			$player->getNetworkSession(),
			$this->network_handler_registry->get($_playerInfo->getValue($player)->getExtraData()["DeviceOS"] ?? -1)
		));
	}

	private function destroy(Player $player) : void{
		if(isset($this->sessions[$player_id = $player->getId()])){
			$this->sessions[$player_id]->finalize();
			unset($this->sessions[$player_id]);
		}
	}

	public function get(Player $player) : PlayerSession{
		return $this->sessions[$player->getId()];
	}

	public function getNullable(Player $player) : ?PlayerSession{
		return $this->sessions[$player->getId()] ?? null;
	}

	public function getNetworkHandlerRegistry() : PlayerNetworkHandlerRegistry{
		return $this->network_handler_registry;
	}
}