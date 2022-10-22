<?php

declare(strict_types=1);

namespace muqsit\invmenu\session;

use Closure;
use muqsit\invmenu\session\network\PlayerNetwork;
use pocketmine\player\Player;
use function spl_object_id;

final class PlayerSession{

	private ?InvMenuInfo $current = null;

	public function __construct(
		private Player $player,
		private PlayerNetwork $network
	){}

	/**
	 * @internal
	 */
	public function finalize() : void{
		if($this->current !== null){
			$this->current->graphic->remove($this->player);
			$this->player->removeCurrentWindow();
		}
		$this->network->finalize();
	}

	public function getCurrent() : ?InvMenuInfo{
		return $this->current;
	}

	/**
	 * @internal use InvMenu::send() instead.
	 *
	 * @param InvMenuInfo|null $current
	 * @param (Closure(bool) : void)|null $callback
	 */
	public function setCurrentMenu(?InvMenuInfo $current, ?Closure $callback = null) : void{
		if($this->current !== null){
			$this->current->graphic->remove($this->player);
		}

		$this->current = $current;

		if($this->current !== null){
			$current_id = spl_object_id($this->current);
			$this->current->graphic->send($this->player, $this->current->graphic_name);
			$this->network->waitUntil(PlayerNetwork::DELAY_TYPE_OPERATION, $this->current->graphic->getAnimationDuration(), function(bool $success) use($callback, $current_id) : bool{
				$current = $this->current;
				if($current !== null && spl_object_id($current) === $current_id){
					if($success){
						$this->network->onBeforeSendMenu($this, $current);
						$result = $current->graphic->sendInventory($this->player, $current->menu->getInventory());
						if($result){
							if($callback !== null){
								$callback(true);
							}
							return false;
						}
					}

					$this->removeCurrentMenu();
				}
				if($callback !== null){
					$callback(false);
				}
				return false;
			});
		}else{
			$this->network->wait(PlayerNetwork::DELAY_TYPE_ANIMATION_WAIT, static function(bool $success) use($callback) : bool{
				if($callback !== null){
					$callback($success);
				}
				return false;
			});
		}
	}

	public function getNetwork() : PlayerNetwork{
		return $this->network;
	}

	/**
	 * @internal use Player::removeCurrentWindow() instead
	 * @return bool
	 */
	public function removeCurrentMenu() : bool{
		if($this->current !== null){
			$this->setCurrentMenu(null);
			return true;
		}
		return false;
	}
}
