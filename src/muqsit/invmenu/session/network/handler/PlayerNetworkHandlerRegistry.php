<?php

declare(strict_types=1);

namespace muqsit\invmenu\session\network\handler;

use Closure;
use muqsit\invmenu\session\network\NetworkStackLatencyEntry;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use function mt_rand;

final class PlayerNetworkHandlerRegistry{

	private PlayerNetworkHandler $default;

	/** @var PlayerNetworkHandler[] */
	private array $game_os_handlers = [];

	public function __construct(){
		$this->registerDefault(new ClosurePlayerNetworkHandler(static function(Closure $then) : NetworkStackLatencyEntry{
			$timestamp = mt_rand();
			return new NetworkStackLatencyEntry($timestamp * 1000000, $then, $timestamp);
		}));
		$this->register(DeviceOS::PLAYSTATION, new ClosurePlayerNetworkHandler(static function(Closure $then) : NetworkStackLatencyEntry{
			$timestamp = mt_rand();
			return new NetworkStackLatencyEntry($timestamp * 1000000, $then, $timestamp * 1000);
		}));
	}

	public function registerDefault(PlayerNetworkHandler $handler) : void{
		$this->default = $handler;
	}

	public function register(int $os_id, PlayerNetworkHandler $handler) : void{
		$this->game_os_handlers[$os_id] = $handler;
	}

	public function get(int $os_id) : PlayerNetworkHandler{
		return $this->game_os_handlers[$os_id] ?? $this->default;
	}
}
