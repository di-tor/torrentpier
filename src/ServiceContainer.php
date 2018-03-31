<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier;

final class ServiceContainer
{
    private $container;

    private static $inst;

    /**
     * ServiceContainer constructor.
     * @internal
     */
    private function __construct()
    {
        $this->container = new \ArrayIterator([]);
    }

    /**
     * @return ServiceContainer
     * @internal
     */
    private static function instance()
    {
        if (!self::$inst) {
            self::$inst = new self;
        }

        return self::$inst;
    }

    public static function set($key, $data)
    {
        self::instance()->container->offsetSet($key, $data);
    }

    public static function has($key)
    {
        return self::instance()->container->offsetExists($key);
    }

    /**
     * Getting instance a service.
     *
     * @param $key
     * @param callable|null $function Create instance if it not exist.
     * @return \ArrayIterator|mixed
     */
    public static function get($key, callable $function = null)
    {
        if (!self::has($key)) {
            self::set($key, $function());
        }

        return self::instance()->container->offsetGet($key);
    }
}
