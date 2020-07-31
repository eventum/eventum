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

namespace Eventum\Logger;

use Eventum\ServiceContainer;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait as PsrLoggerTrait;

trait LoggerTrait
{
    use PsrLoggerTrait;

    /** @var LoggerInterface */
    protected $logger;

    public function log($level, $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = ServiceContainer::getLogger();
        }

        return $this->logger;
    }
}
