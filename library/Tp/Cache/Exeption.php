<?php

namespace Tp\Cache;

use Zend\Cache\StorageFactory;

class Exeption {
	const MEMCACHED = 'memcached';
	protected $cache;
	
	function __construct($ttl) {
		$this->cache = StorageFactory::factory($this->options($ttl));	
	}
	
	private function adapter() {
		switch($this->config('cache_new')['adapter']) {
			case 'memcached':
				$opt = [[$this->config('cache_new')['host'], $this->config('cache_new')['port']]];
			break;
			default:
				$opt = '';
		}
		return $opt;
	}
	
	private function options($ttl) {	
		$config = [
			'adapter' => [
			   'name' => $this->config('cache_new')['adapter'],
			   'options' => [
					'ttl' => $ttl,
					'servers' => $this->adapter(),
					'lib_options' => [
						'prefix_key' => 'myapp_'
					]
				]
			]
		];
		
		return $config;
	}
	
	private function config($name) {
		global $bb_cfg;
		return (isset($bb_cfg[$name])) ? $bb_cfg[$name] : '';
	}
	
	function cache() {
		return $this->cache;
	}
}