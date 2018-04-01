<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Legacy\Datastore;

use Psr\SimpleCache\InvalidArgumentException;
use TorrentPier\Cache\Connection;

/**
 * Class Cache
 * @package TorrentPier\Legacy\Datastore
 *
 * @deprecated
 */
class Cache extends Common
{
    /**
     * @var Connection
     */
    private $cacheConnection;

    public function __construct(Connection $cacheConnection)
    {
        $this->cacheConnection = $cacheConnection;
    }

    /**
     * @param $title
     * @param $var
     * @return bool
     */
    public function store($title, $var)
    {
        $this->data[$title] = $var;

        $this->cur_query = "cache->set('$title')";
        $this->debug('start');
        $this->debug('stop');
        $this->cur_query = null;
        $this->num_queries++;

        try {
            $this->cacheConnection->set($title, $var);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    public function clean()
    {
        $this->cacheConnection->clear();
    }

    public function _fetch_from_store()
    {
        if (!$items = $this->queued_items) {
            return;
        }

        foreach ($items as $item) {
            $this->cur_query = "cache->get('$item')";
            $this->debug('start');
            $this->debug('stop');
            $this->cur_query = null;
            $this->num_queries++;

            try {
                $this->data[$item] = $this->cacheConnection->get($item);
            } catch (InvalidArgumentException $e) {
            }
        }
    }
}
