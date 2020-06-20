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

namespace Eventum\Mail;

use Misc;

class MessageIdGenerator
{
    /** @var string */
    private $hostname;

    public function __construct(string $hostname)
    {
        $this->hostname = $hostname;
    }

    public function generate(): string
    {
        // first part is time based
        $first = $this->convertBase(microtime(true));

        // second part is random string
        $second = $this->convertBase(bin2hex(Misc::generateRandom(8)));

        return '<eventum.md5.' . $first . '.' . $second . '@' . $this->hostname . '>';
    }

    private function convertBase(string $input): string
    {
        return base_convert($input, 16, 36);
    }
}
