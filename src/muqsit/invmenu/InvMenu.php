<?php

declare(strict_types=1);

namespace muqsit\invmenu;

use Closure;
use LogicException;
use muqsit\invmenu\inventory\SharedInvMenuSynchronizer;
use muqsit\invmenu\session\InvMenuInfo;
use muqsit\invmenu\session\PlayerWindowDispatcher;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\transaction\SimpleInvMenuTransaction;
use muqsit\invmenu\type\InvMenuType;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

class InvMenu implements InvMenuTypeIds{

	/**
	 * @param string $identifier
	 * @param mixed ...$args
	 * @return InvMenu
	 */
	public static function create(string $identifier, ...$args) : InvMenu{
		return new InvMenu(InvMenuHandler::getTypeRegistry()->get($identifier), ...$args);
	}

	/**
	 * @param (Closure(DeterministicInvMenuTransaction) : void)|null $listener
	 * @return Closure(InvMenuTransaction) : InvMenuTransactionResult
	 */
	public static function readonly(?Closure $listener = null) : Closure{
		return static function(InvMenuTransaction $transaction) use($listener) : InvMenuTransactionResult{
			$result = $transaction->discard();
			if($listener !== null){
				$listener(new DeterministicInvMenuTransaction($transaction, $result));
			}
			return $result;
		};
	}

	readonly public InvMenuType $type;
	protected ?string $name = null;
	protected ?Closure $listener = null;
	protected ?Closure $inventory_close_listener = null;
	protected Inventory $inventory;
	protected ?SharedInvMenuSynchronizer $synchronizer = null;

	public function __construct(InvMenuType $type, ?Inventory $custom_inventory = null){
		InvMenuHandler::isRegistered() || throw new LogicException("Tried creating menu before calling " . InvMenuHandler::class . "::register()");
		$this->type = $type;
		$this->inventory = $this->type->createInventory();
		$this->setInventory($custom_inventory);
	}

	public function __destruct(){
		$this->setInventory(null);
	}

	public function getName() : ?string{
		return $this->name;
	}

	public function setName(?string $name) : self{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return (Closure(InvMenuTransaction) : InvMenuTransactionResult)|null
	 */
	public function getListener() : ?Closure{
		return $this->listener;
	}

	/**
	 * @param (Closure(InvMenuTransaction) : InvMenuTransactionResult)|null $listener
	 * @return self
	 */
	public function setListener(?Closure $listener) : self{
		$this->listener = $listener;
		return $this;
	}

	/**
	 * @return (Closure(Player, Inventory) : void)|null
	 */
	public function getInventoryCloseListener() : ?Closure{
		return $this->inventory_close_listener;
	}

	/**
	 * @param (Closure(Player, Inventory) : void)|null $listener
	 * @return self
	 */
	public function setInventoryCloseListener(?Closure $listener) : self{
		$this->inventory_close_listener = $listener;
		return $this;
	}

	public function getInventory() : Inventory{
		return $this->inventory;
	}

	public function setInventory(?Inventory $custom_inventory) : void{
		if($this->synchronizer !== null){
			$this->synchronizer->destroy();
			$this->synchronizer = null;
		}

		if($custom_inventory !== null){
			$this->synchronizer = new SharedInvMenuSynchronizer($this, $custom_inventory);
		}
	}

	/**
	 * @param Player $player
	 * @param string|null $name
	 * @param (Closure(bool) : void)|null $callback
	 */
	final public function send(Player $player, ?string $name = null, ?Closure $callback = null) : void{
		$player->removeCurrentWindow();

		$session = InvMenuHandler::getPlayerManager()->get($player);
		if($session->dispatcher !== null){
			$session->dispatcher->then($this, $name, $callback);
			return;
		}

		$graphic = $this->type->createGraphic($this, $player);
		if($graphic === null){
			if($callback !== null){
				$callback(false);
			}
			return;
		}

		$session->dispatcher = new PlayerWindowDispatcher($session, new InvMenuInfo($this, $graphic, $name));
		if($callback !== null){
			$session->dispatcher->addCallback($callback);
		}
	}

	public function handleInventoryTransaction(Player $player, Item $out, Item $in, SlotChangeAction $action, InventoryTransaction $transaction) : InvMenuTransactionResult{
		$inv_menu_txn = new SimpleInvMenuTransaction($player, $out, $in, $action, $transaction);
		return $this->listener !== null ? ($this->listener)($inv_menu_txn) : $inv_menu_txn->continue();
	}

	public function onClose(Player $player) : void{
		if($this->inventory_close_listener !== null){
			($this->inventory_close_listener)($player, $this->getInventory());
		}
	}
}
