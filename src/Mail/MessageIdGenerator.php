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

    public function generateWithSeed(string $first, string $second): string
    {
        return $this->format($first, $second);
    }

    public function generate(): string
    {
        // first part is time based
        $first = microtime(true);

        // second part is random string
        $second = bin2hex(Misc::generateRandom(16));

        return $this->format($first, $second);
    }

    private function format(string $first, string $second): string
    {
        return '<eventum.' . $this->convert($first) . '.' . $this->convert($second) . '@' . $this->hostname . '>';
    }

    private function convert(string $input): string
    {
        $filtered = preg_replace('/\D+/', '', $input);

        return base_convert($filtered, 16, 36);
    }
}
