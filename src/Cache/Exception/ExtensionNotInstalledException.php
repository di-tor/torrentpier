<?php
/**
 * TorrentPier – Bull-powered BitTorrent tracker engine
 *
 * @copyright Copyright (c) 2005-2018 TorrentPier (https://torrentpier.com)
 * @link      https://github.com/torrentpier/torrentpier for the canonical source repository
 * @license   https://github.com/torrentpier/torrentpier/blob/master/LICENSE MIT License
 */

namespace TorrentPier\Cache\Exception;

use \Exception;
use Throwable;

class ExtensionNotInstalledException extends Exception
{
    public function __construct($message = 'Required extension not installed', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
