<?php

namespace Tp\Cache;

use Zend\Cache\StorageFactory;

class Factory {
	protected $cache;
	
	function __construct($ttl) {
		$this->cache = StorageFactory::factory($this->options($ttl));	
	}
	
	private function adapter() {
		$adapter = '\/'. $this->config('cache_new')['adapter'];
		$adapter = str_replace('/', '', $adapter);
		$class = 'Tp\Cache\Adapter' . $adapter;
		
		if(class_exists($class)) {
            $opt = new $class;
			return $opt->config();
        } else {
			throw new Error('Cache adapter not found');
		}
	
	}
	
	private function options($ttl) {

		$options = [
			'ttl' => $ttl,
			'lib_options' => [
				'prefix_key' => $this->config('cache_new')['prefix']
			]
		];
		
		$options = array_merge($options, $this->adapter());
		
		$config = [
			'adapter' => [
			   'name' => $this->config('cache_new')['adapter'],
			   'options' => $options,
			],
			'plugins' => [
				'Serializer'
			]
		];

		return $config;
	}
	
	private function config($name) {
		global $bb_cfg;
		return (isset($bb_cfg[$name])) ? $bb_cfg[$name] : '';
	}
	
	public function cache() {
		return $this->cache;
	}
}