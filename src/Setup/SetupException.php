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

namespace Eventum\Setup;

use RuntimeException;

class SetupException extends RuntimeException
{
    /** @var string */
    private $type;

    public function __construct($type = '', $details = '')
    {
        parent::__construct($details, 0);
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }
}
