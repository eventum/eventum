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

namespace Eventum;

use League\CommonMark\ConverterInterface;
use League\CommonMark\CommonMarkConverter;

class Markdown
{
    /** @var ConverterInterface */
    private $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    public function render(string $text): string
    {
        return $this->converter->convertToHtml($text);
    }
}
