<?php

declare(strict_types=1);

namespace muqsit\invmenu\transaction;

use Closure;
use pocketmine\player\Player;

final class InvMenuTransactionResult{

	/** @var (Closure(Player) : void)|null */
	public ?Closure $post_transaction_callback = null;

	public function __construct(
		readonly public bool $cancelled
	){}

	/**
	 * Notify when we have escaped from the event stack trace and the
	 * client's network stack trace.
	 * Useful for sending forms and other stuff that cant be sent right
	 * after closing inventory.
	 *
	 * @param (Closure(Player) : void)|null $callback
	 * @return self
	 */
	public function then(?Closure $callback) : self{
		$this->post_transaction_callback = $callback;
		return $this;
	}
}