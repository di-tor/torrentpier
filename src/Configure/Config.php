<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Configure;

use TorrentPier\Configure\Reader\ReaderInterface;

class Config
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var ReaderInterface[]|array
     */
    private $loaders;

    /**
     * Config constructor.
     * @param ReaderInterface[]|array $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
        $this->configure();
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Get value by key from config.
     * @param $key
     * @return int|string|null
     */
    public function get($key)
    {
        $keys = explode('.', $key);
        $countKeys = count($keys);

        $tmpData = $this->data;

        for ($i = 0; $i < $countKeys; $i++) {
            $item = (string) $keys[$i];
            if (!array_key_exists($item, $tmpData)) {
                break;
            }

            if ($i < ($countKeys - 1) && is_array($tmpData[$item])) {
                $tmpData = $tmpData[$item];
            } else {
                return $tmpData[$item];
            }
        }

        return null;
    }

    /**
     * Configure
     */
    protected function configure()
    {
        foreach ($this->loaders as $loader) {
            $this->data = array_merge((array)$this->data, $loader->compile());
        }
    }
}
