<?php isset($config) or die('Welcome to Torrent Pier');
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

/**
 * Path to application
 */
$config['root_path'] = dir(__DIR__);

/**
 * Database
 */
$config['database'] = [
    'host'     => env('DB_HOST'),
    'dbname'   => env('DB_DATABASE'),
    'user'     => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
];

/**
 * Cache setting
 *
 * Providers: apcu, array, chain, -couchbase, filesystem, memcache, memcached, mongodb,
 *           phpfile, predis, redis, riak, sqlite3, void, wincache, xcache, zenddata.
 *
 * Current work: apcu, array, chain, filesystem, memcached, phpfile, redis, sqlite3, void.
 * In dev: couchbase, memcache, mongodb, predis, riak, wincache, xcache, zenddata.
 */
$config['cache'] = [
    'provider' => 'filesystem',
    'options' => [
        'directory' => dirname(__DIR__) . '/internal_data/cache',
    ],
];
