<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Psr\SimpleCache\CacheInterface;
use TorrentPier\Cache\Exception\InvalidArgumentException;
use TorrentPier\Cache\Traits\Normalize;
use TorrentPier\Cache\Traits\Validator;

class Connection implements CacheInterface
{
    use Normalize;
    use Validator;

    const NAMESPACE_NAME = '__TORRENT_PIER__';

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
        $this->cacheProvider->setNamespace(self::NAMESPACE_NAME);
    }

    /**
     * @return CacheProvider
     */
    public function getCacheProvider()
    {
        return $this->cacheProvider;
    }

    /**
     * @param $namespace
     */
    public function setNamespace($namespace)
    {
        $this->cacheProvider->setNamespace($namespace);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->cacheProvider->getNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        if (!$this->validateKey($key)) {
            throw new InvalidArgumentException();
        }

        if ($this->has($key)) {
            return $this->cacheProvider->fetch($key);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        if (!$this->validateKey($key)) {
            throw new InvalidArgumentException();
        }

        $ttl = $this->timeLifeNormalize($ttl);

        return $this->cacheProvider->save($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        if (!$this->validateKey($key)) {
            throw new InvalidArgumentException();
        }

        return $this->cacheProvider->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->cacheProvider->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (!$this->validateKeys($keys)) {
            throw new InvalidArgumentException();
        }

        return $this->cacheProvider->fetchMultiple($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = 0)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentException();
        }

        if (!$this->validateKeys(array_keys($values))) {
            throw new InvalidArgumentException();
        }

        $values = $this->valuesNormalize($values);
        $ttl = $this->timeLifeNormalize($ttl);

        return $this->cacheProvider->saveMultiple($values, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        if (!$this->validateKeys($keys)) {
            throw new InvalidArgumentException();
        }

        return $this->cacheProvider->deleteMultiple($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if (!$this->validateKey($key)) {
            throw new InvalidArgumentException();
        }

        return $this->cacheProvider->contains($key);
    }
}
