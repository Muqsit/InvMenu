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

namespace muqsit\invmenu\transaction;

use Closure;

final class InvMenuTransactionResult{

	/** @var bool */
	private $cancelled;

	/** @var Closure|null */
	private $post_transaction_callback;

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
	 * @phpstan-param Closure(\pocketmine\Player) : void $callback
	 */
	public function then(?Closure $callback) : self{
		$this->post_transaction_callback = $callback;
		return $this;
	}

	public function getPostTransactionCallback() : ?Closure{
		return $this->post_transaction_callback;
	}
}