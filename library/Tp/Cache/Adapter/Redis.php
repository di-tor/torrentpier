<?php

namespace Tp\Cache\Adapter;

class Redis {
	
	function config() {
		return [
			'server' => [
				'host' => '127.0.0.1',
				'port' => '6379',
			]
		];
	}
}