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

namespace Eventum\Markdown;

use HTMLPurifier;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\EnvironmentInterface;

final class MarkdownRenderer implements MarkdownRendererInterface
{
    /** @var HTMLPurifier */
    private $purifier;
    /** @var CommonMarkConverter */
    private $converter;

    public function __construct(CommonMarkConverter $converter, HTMLPurifier $purifier)
    {
        $this->converter = $converter;
        $this->purifier = $purifier;
    }

    public function render(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->converter->convertToHtml($text);
        $html = $this->purifier->purify($html);

        return $html;
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->converter->getEnvironment();
    }
}
