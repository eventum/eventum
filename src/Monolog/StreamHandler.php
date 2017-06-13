<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Monolog;

use Monolog;
use Monolog\Handler\StreamHandler as BaseStreamHandler;

/**
 * Class StreamHandler override Monolog StreamHandler to set file permissions only for new files
 */
class StreamHandler extends BaseStreamHandler
{
    /**
     * {@inheritdoc}
     */
    public function __construct($stream, $level = Monolog\Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);

        // do not set file permission if file already exists
        // NOTE: non local urls (http, https) not supported here
        if ($this->url && file_exists($this->url)) {
            $this->filePermission = null;
        }
    }
}
