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

namespace Eventum;

use malkusch\lock\mutex\FlockMutex;
use RuntimeException;

class ConcurrentLock
{
    private $mutex;

    public function __construct($lockname)
    {
        $lockfile = APP_LOCKS_PATH . '/' . $lockname . '.lck';

        $fh = fopen($lockfile, 'cb');
        if (!$fh) {
            throw new RuntimeException("Unable to create lock file: $lockfile");
        }

        $this->mutex = new FlockMutex($fh);
    }

    public function synchronized(callable $code)
    {
        $this->mutex->synchronized($code);
    }
}
