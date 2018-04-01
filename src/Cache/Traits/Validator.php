<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Cache\Traits;

trait Validator
{
    /**
     * Validate key.
     *
     * @param $key
     * @return bool
     */
    protected function validateKey($key)
    {
        return is_string($key);
    }

    /**
     * Validated keys.
     *
     * @param array $keys
     * @return bool
     */
    protected function validateKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->validateKey($key)) {
                return false;
            }
        }

        return true;
    }
}
