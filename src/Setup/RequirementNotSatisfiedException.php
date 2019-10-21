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
use Throwable;

class RequirementNotSatisfiedException extends RuntimeException
{
    /** @var string[] */
    private $errors;

    public function __construct(array $errors, Throwable $previous = null)
    {
        parent::__construct('Requirements not satisfied', 0, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
