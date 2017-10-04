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

namespace Eventum\Db;

use PDOException;

class DatabaseException extends PDOException
{
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, 0, $previous);

        // PDOException code is 'HY000' in MySQL workaround
        $this->code = $code;
    }
}
