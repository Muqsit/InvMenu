<?php

declare(strict_types=1);

namespace muqsit\invmenu\session\network;

use Closure;

final class NetworkStackLatencyEntry{

	readonly public int $timestamp;
	readonly public int $network_timestamp;
	readonly public Closure $then;
	public float $sent_at = 0.0;

	public function __construct(int $timestamp, Closure $then, ?int $network_timestamp = null){
		$this->timestamp = $timestamp;
		$this->then = $then;
		$this->network_timestamp = $network_timestamp ?? $timestamp;
	}
}