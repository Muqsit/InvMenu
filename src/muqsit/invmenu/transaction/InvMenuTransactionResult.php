<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use Closure;

final class InvMenuTransactionResult{

	/** @var (Closure(\pocketmine\player\Player) : void)|null */
	private ?Closure $post_transaction_callback = null;

	public function __construct(
		private bool $cancelled
	){}

	public function isCancelled() : bool{
		return $this->cancelled;
	}

	/**
	 * Notify when we have escaped from the event stack trace and the
	 * client's network stack trace.
	 * Useful for sending forms and other stuff that cant be sent right
	 * after closing inventory.
	 *
	 * @param (Closure(\pocketmine\player\Player) : void)|null $callback
	 * @return self
	 */
	public function then(?Closure $callback) : self{
		$this->post_transaction_callback = $callback;
		return $this;
	}

	public function getPostTransactionCallback() : ?Closure{
		return $this->post_transaction_callback;
	}
}