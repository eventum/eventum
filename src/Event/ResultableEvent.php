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

namespace Eventum\Event;

use InvalidArgumentException;

/**
 * Event which has state.
 *
 * Event consumers should call setResult() to indicate they want that value to be outcome.
 */
class ResultableEvent extends EventContext
{
    /**
     * @var mixed any data except null
     */
    private $result;

    /**
     * Tells whether any consumer has set the result
     */
    public function hasResult(): bool
    {
        return $this->result !== null;
    }

    /**
     * Set the result of the event
     */
    public function setResult($result): void
    {
        if ($result === null) {
            throw new InvalidArgumentException('Can not set value as null');
        }

        $this->result = $result;
    }

    /**
     * Get the result of the event propagation
     */
    public function getResult()
    {
        return $this->result;
    }
}
