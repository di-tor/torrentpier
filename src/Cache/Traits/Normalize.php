<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Cache\Traits;

trait Normalize
{
    /**
     * Normalize time to life.
     *
     * @param null|int|\DateTime $ttl
     * @return int
     */
    protected function timeLifeNormalize($ttl)
    {
        if ($ttl instanceof \DateTime) {
            $ttl = $ttl->getTimestamp() - time();
        }

        if (!$ttl || $ttl < 0) {
            $ttl = 0;
        }

        return $ttl;
    }

    /**
     * Normalize values.
     *
     * @param iterable|array|\Traversable $values
     * @return array
     */
    protected function valuesNormalize($values)
    {
        if ($values instanceof \Traversable) {
            return iterator_to_array($values);
        }

        return $values;
    }
}
