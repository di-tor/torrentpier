<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Cache;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\CouchbaseCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\MongoDBCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\RiakCache;
use Doctrine\Common\Cache\SQLite3Cache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\WinCacheCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\Cache\ZendDataCache;
use TorrentPier\Cache\Exception\ExtensionNotInstalledException;
use TorrentPier\Cache\Exception\NotSupportProviderException;

final class CacheProviderManager
{
    const PROVIDER_APCU         = 'apcu';
    const PROVIDER_ARRAY        = 'array';
    const PROVIDER_CHAIN        = 'chain';
//    const PROVIDER_COUCHBASE    = 'couchbase';
    const PROVIDER_FILESYSTEM   = 'filesystem';
//    const PROVIDER_MEMCACHE     = 'memcache';
    const PROVIDER_MEMCACHED    = 'memcached';
//    const PROVIDER_MONGODB      = 'mongodb';
    const PROVIDER_PHPFILE      = 'phpfile';
//    const PROVIDER_PREDIS       = 'predis';
    const PROVIDER_REDIS        = 'redis';
//    const PROVIDER_RIAK         = 'riak';
    const PROVIDER_SQLITE3      = 'sqlite3';
    const PROVIDER_VOID         = 'void';
//    const PROVIDER_WINCACHE     = 'wincache';
    const PROVIDER_XCACHE       = 'xcache';
    const PROVIDER_ZENDDATA     = 'zenddata';

    private static $listProviders = [
        self::PROVIDER_APCU         => ApcuCache::class,
        self::PROVIDER_ARRAY        => ArrayCache::class,
        self::PROVIDER_CHAIN        => ChainCache::class,
//        self::PROVIDER_COUCHBASE    => CouchbaseCache::class,
        self::PROVIDER_FILESYSTEM   => FilesystemCache::class,
//        self::PROVIDER_MEMCACHE     => MemcacheCache::class,
        self::PROVIDER_MEMCACHED    => MemcachedCache::class,
//        self::PROVIDER_MONGODB      => MongoDBCache::class,
        self::PROVIDER_PHPFILE      => PhpFileCache::class,
//        self::PROVIDER_PREDIS       => PredisCache::class,
        self::PROVIDER_REDIS        => RedisCache::class,
//        self::PROVIDER_RIAK         => RiakCache::class,
        self::PROVIDER_SQLITE3      => SQLite3Cache::class,
        self::PROVIDER_VOID         => VoidCache::class,
//        self::PROVIDER_WINCACHE     => WinCacheCache::class,
        self::PROVIDER_XCACHE       => XcacheCache::class,
        self::PROVIDER_ZENDDATA     => ZendDataCache::class,
    ];

    private function __construct()
    {
    }

    /**
     * @param array $params
     * @return Connection
     * @throws \Exception
     */
    public static function getConnection(array $params = [])
    {
        if (!$params) {
            $params['provider'] = 'array';
        }

        if (!isset($params['options'])) {
            $params['options'] = [];
        }

        if (!isset(self::$listProviders[$params['provider']])) {
            throw new NotSupportProviderException();
        }

        $cacheProvider = self::configureProvider($params['provider'], $params['options']);

        return new Connection($cacheProvider);
    }

    /**
     * @param string $keyProvider
     * @param array $options
     * @return CacheProvider
     * @throws ExtensionNotInstalledException
     */
    private static function configureProvider($keyProvider, array $options = [])
    {
        $classProvider = self::$listProviders[$keyProvider];

        switch ($keyProvider) {
            case self::PROVIDER_APCU:
                if (!function_exists('apcu_fetch')) {
                    throw new ExtensionNotInstalledException('The apcu extension is not installed');
                }

                return new $classProvider();
            case self::PROVIDER_ARRAY:
                return new $classProvider();
            case self::PROVIDER_CHAIN:
                $providers = [];
                $options = isset($options['providers']) ? $options['providers'] : $options;
                foreach ($options as $key => $provider) {
                    if (is_int($key)) {
                        $providers[] = self::configureProvider($provider);
                    } else {
                        $providers[] = self::configureProvider($key, $provider);
                    }
                }

                return new $classProvider($providers);
//            case self::PROVIDER_COUCHBASE:
            case self::PROVIDER_FILESYSTEM:
                return new $classProvider(
                    $options['directory'],
                    $options['extension'] = FilesystemCache::EXTENSION,
                    $options['umask'] = 0002
                );
//            case self::PROVIDER_MEMCACHE:
            case self::PROVIDER_MEMCACHED:
                if (!class_exists(\Memcached::class)) {
                    throw new ExtensionNotInstalledException('The memcached extension is not installed');
                }

                $memcached = new \Memcached();
                $memcached->addServer('tp_memcached', 11211, 100);
                /** @var MemcachedCache $cacheProvider */
                $cacheProvider = new $classProvider();
                $cacheProvider->setMemcached($memcached);

                return $cacheProvider;
//            case self::PROVIDER_MONGODB:
            case self::PROVIDER_PHPFILE:
                return new $classProvider(
                    $options['directory'],
                    $options['extension'] = PhpFileCache::EXTENSION,
                    $options['umask'] = 0002
                );
//            case self::PROVIDER_PREDIS:
            case self::PROVIDER_REDIS:
                if (!class_exists(\Redis::class)) {
                    throw new ExtensionNotInstalledException('The redis extension is not installed');
                }

                $redis = new \Redis();
                $redis->connect(
                    isset($options['host']) ? $options['host'] : '127.0.0.1',
                    isset($options['port']) ? $options['port'] : 6379,
                    isset($options['timeout']) ? $options['timeout'] : 0,
                    isset($options['reserved']) ? $options['reserved'] : null,
                    isset($options['retry_interval']) ? $options['retry_interval'] : 0
                );
                /** @var RedisCache $cacheProvider */
                $cacheProvider = new $classProvider();
                $cacheProvider->setRedis($redis);

                return $cacheProvider;
//            case self::PROVIDER_RIAK:
            case self::PROVIDER_SQLITE3:
                if (!class_exists(\SQLite3::class)) {
                    throw new ExtensionNotInstalledException('The sqlite3 extension is not installed');
                }

                $sqlite3 = new \SQLite3(
                    $options['filename'],
                    isset($options['flags']) ? $options['flags'] : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
                    isset($options['encryption_key']) ? $options['encryption_key'] : null
                );

                return new $classProvider(
                    $sqlite3,
                    isset($options['table']) ? $options['table'] : 'tp_cache'
                );
            case self::PROVIDER_VOID:
                return new $classProvider();
//            case self::PROVIDER_WINCACHE:
//            case self::PROVIDER_XCACHE:
//                if (!function_exists('xcache_get')) {
//                    throw new ExtensionNotInstalledException('The xcache extension is not installed');
//                }
//
//                return new $classProvider();
//            case self::PROVIDER_ZENDDATA:
//                if (!function_exists('zend_shm_cache_fetch')) {
//                    throw new ExtensionNotInstalledException('The apcu extension is not installed');
//                }
//
//                return new $classProvider();
            default:
                return self::configureProvider(self::PROVIDER_ARRAY);
        }
    }
}
