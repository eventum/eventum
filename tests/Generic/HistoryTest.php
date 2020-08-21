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

namespace Eventum\Test\Generic;

use Eventum\Test\TestCase;
use Misc;

class HistoryTest extends TestCase
{
    public function testHistoryContext(): void
    {
        $message = "Issue updated to status '{status}' by {actor}";
        $context = [
            'status' => 'closed',
            'actor' => 'Random User',
        ];
        $message = Misc::processTokens($message, $context);
        $exp = "Issue updated to status 'closed' by Random User";
        $this->assertEquals($exp, $message);
    }

    public function testHistoryContextJson(): void
    {
        $message = "Issue updated to status '{status}' by {actor}";
        $context = '{"status":"closed","actor":"Random User"}';
        $message = Misc::processTokens($message, $context);
        $exp = "Issue updated to status 'closed' by Random User";
        $this->assertEquals($exp, $message);
    }
}
