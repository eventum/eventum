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

namespace Eventum\Mail\Exception;

use Exception;

class RoutingException extends Exception
{
    /** data format error */
    const EX_DATAERR = 65;

    /** cannot open input */
    const EX_NOINPUT = 66;

    /** addressee unknown */
    const EX_NOUSER = 67;

    /** permission denied */
    const EX_NOPERM = 77;

    /** configuration error */
    const EX_CONFIG = 78;

    public static function noMessageBodyError()
    {
        $message = ev_gettext('Error: The email message was empty.');

        return new self($message, self::EX_NOINPUT);
    }

    public static function noRecipientError()
    {
        $message = ev_gettext(
            'Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.'
        );

        return new self($message, self::EX_DATAERR);
    }

    /**
     * @param int $issue_id
     * @return RoutingException
     */
    public static function noIssuePermission($issue_id)
    {
        $message = ev_gettext(
            'Error: The sender of this email is not allowed in the project associated with issue #%d.',
            $issue_id
        );

        return new self($message, self::EX_NOPERM);
    }

    public static function noDraftRouting()
    {
        $message = ev_gettext('Error: The email draft interface is disabled.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noEmailRouting()
    {
        $message = ev_gettext('Error: The email routing interface is disabled.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noNoteRouting()
    {
        $message = ev_gettext('Error: The internal note routing interface is disabled.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noEmailPrefixConfigured()
    {
        $message = ev_gettext('Error: Please configure the email address prefix.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noEmailDomainConfigured()
    {
        $message = ev_gettext('Error: Please configure the email address domain.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noAssociatedUserConfigured()
    {
        $message = ev_gettext('Error: The associated user for the email routing interface needs to be set.');

        return new self($message, self::EX_CONFIG);
    }

    public static function noEmaiAccountConfigured()
    {
        $message = ev_gettext('Error: Please provide the email account ID.');

        return new self($message, self::EX_CONFIG);
    }
}
