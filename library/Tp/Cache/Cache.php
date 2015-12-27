<?php

namespace Tp\Cache;

class Cache {
	protected $exp;
	static $_ttl = '3600';

	private static function getClass($ttl = false) {
		
		if(!$ttl) $ttl = self::$_ttl;
		
		return new Exeption($ttl);
	}
	
	public static function get($key) {

		$class = self::getClass();
	
		return $class->cache()->getItem($key);
	}
	
	public static function set($key, $val, $ttl = false) {
		$class = self::getClass($ttl);
	
		return $class->cache()->setItem($key, $val);
	}
	
	public static function remove($key) {
		
		$class = self::getClass();
	
		return $class->cache()->removeItem($key);
	}
	
	public static function replace($key, $val, $ttl = false) {
		
		$class = self::getClass($ttl);
	
		return $class->cache()->replaceItem($key, $val);
	}
	
	public static function check($key) {
		
		$class = self::getClass();
	
		return $class->cache()->hasItem($key);
	}
}