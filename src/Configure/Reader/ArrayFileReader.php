<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Configure\Reader;

class ArrayFileReader extends ArrayReader
{
    /**
     * ArrayFileConfig constructor.
     * @param $pathToFile
     * @param string $name
     */
    public function __construct($pathToFile, $name = 'config')
    {
        ${$name} = [];

        require $pathToFile;

        parent::__construct(${$name});

    }
}
