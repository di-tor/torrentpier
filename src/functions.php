<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier;

use TorrentPier\Configure\Config;
use TorrentPier\Configure\Reader\ArrayFileReader;
use TorrentPier\ServiceContainer as SC;

/**
 * Configure application.
 *
 * @return Config
 */
function config()
{
    return SC::get('config', function () {
        return new Config([
            new ArrayFileReader(dirname(__DIR__) . '/library/config.php')
        ]);
    });
}
