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

namespace Eventum\Config;

use Laminas\Config\Config as LaminasConfig;

class Config extends LaminasConfig
{
    /**
     * Override to have reference access
     *
     * {@inheritdoc}
     */
    public function &offsetGet($offset)
    {
        if (array_key_exists($offset, $this->data)) {
            $value = &$this->data[$offset];
        } else {
            $value = null;
        }

        return $value;
    }
}
