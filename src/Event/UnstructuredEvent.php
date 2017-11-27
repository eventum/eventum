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
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @deprecated since 3.4.0 use GenericEvent instead
 */
class UnstructuredEvent extends GenericEvent
{
    /**
     * Get all parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->getArguments();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getArgument($name);
    }

    public function __set($name, $value)
    {
        if (!$this->hasArgument($name)) {
            throw new InvalidArgumentException("Invalid parameter: $name");
        }

        $this->setArgument($name, $value);
    }

    public function __isset($name)
    {
        return $this->hasArgument($name);
    }
}
