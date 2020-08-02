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
use InvalidStateException;

final class DeterministicInvMenuTransaction extends InvMenuTransaction{

	/** @var InvMenuTransactionResult */
	private $result;

	public function __construct(InvMenuTransaction $transaction, InvMenuTransactionResult $result){
		parent::__construct($transaction->getPlayer(), $transaction->getOut(), $transaction->getIn(), $transaction->getAction(), $transaction->getTransaction());
		$this->result = $result;
	}

	public function continue() : InvMenuTransactionResult{
		throw new InvalidStateException("Cannot change state of deterministic transactions");
	}

	public function discard() : InvMenuTransactionResult{
		throw new InvalidStateException("Cannot change state of deterministic transactions");
	}

	public function then(?Closure $callback) : void{
		$this->result->then($callback);
	}
}