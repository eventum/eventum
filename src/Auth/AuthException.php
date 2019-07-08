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

namespace Eventum\Auth;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

class AuthException extends AuthenticationException
{
    public const EMPTY_LOGIN = 1;
    public const EMPTY_PASSWORD = 2;
    public const UNKNOWN_USER = 3;
    public const WRONG_PASSWORD = 3;
    public const LOGGED_OUT = 6;
    public const INACTIVE_USER = 7;
    public const ACCOUNT_ACTIVATED = 8;
    public const PENDING_USER = 9;
    public const ACCOUNT_BACKOFF_LOCKED = 13;

    // use message that's most suitable
    public const UNKNOWN_ERROR = 3;

    public function __construct($message = 'Unknown error', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code ?: self::UNKNOWN_ERROR, $previous);
    }
}
