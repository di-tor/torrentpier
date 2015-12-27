<?php

namespace Tp\Cache;

class Cache {
	protected $exp;
	static $_ttl = '3600';

	private static function getClass($ttl = false) {
		$ttl = ($ttl) ? $ttl : self::$_ttl;
		$cache = new Factory($ttl);
		return $cache->cache();
	}
	
	public static function get($key) {
		$class = self::getClass();
		return $class->getItem($key);
	}
	
	public static function set($key, $val, $ttl = false) {
		$class = self::getClass($ttl);
		return $class->setItem($key, $val);
	}
	
	public static function remove($key) {	
		$class = self::getClass();
		return $class->removeItem($key);
	}
	
	public static function replace($key, $val, $ttl = false) {	
		$class = self::getClass($ttl);
		return $class->replaceItem($key, $val);
	}
	
	public static function check($key) {
		$class = self::getClass();
		return $class->hasItem($key);
	}
}