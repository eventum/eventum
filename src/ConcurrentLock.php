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

use Eventum\Config\Paths;
use Symfony\Component\Lock\Exception\LockStorageException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;

class ConcurrentLock
{
    /** @var LockInterface */
    private $lock;

    /**
     * @param string $lockName
     * @param float $ttl Maximum expected lock duration in seconds
     * @throws LockStorageException
     */
    public function __construct($lockName, $ttl = null)
    {
        $store = new FlockStore(Paths::APP_LOCKS_PATH);
        $this->lock = (new LockFactory($store))->createLock($lockName, $ttl);
    }

    public function synchronized(callable $code): void
    {
        $this->lock->acquire(true);

        try {
            $code();
        } finally {
            $this->lock->release();
        }
    }
}
