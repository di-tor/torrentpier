<?php

namespace Tp\Cache\Adapter;

class Filesystem {
	
	function config() {
		return [
			'cache_dir' => CACHE_DIR,
		];
	}
}