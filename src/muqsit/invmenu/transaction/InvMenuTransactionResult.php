<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use Closure;
use pocketmine\player\Player;

final class InvMenuTransactionResult{

	private bool $cancelled;
	private ?Closure $post_transaction_callback = null;

	public function __construct(bool $cancelled){
		$this->cancelled = $cancelled;
	}

	public function isCancelled() : bool{
		return $this->cancelled;
	}

	/**
	 * Notify when we have escaped from the event stack trace and the
	 * client's network stack trace.
	 * Useful for sending forms and other stuff that cant be sent right
	 * after closing inventory.
	 *
	 * @param Closure|null $callback
	 * @return self
	 *
	 * @phpstan-param Closure(Player) : void $callback
	 */
	public function then(?Closure $callback) : self{
		$this->post_transaction_callback = $callback;
		return $this;
	}

	public function getPostTransactionCallback() : ?Closure{
		return $this->post_transaction_callback;
	}
}