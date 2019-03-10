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
use League\CommonMark\Environment;

class Markdown
{
    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    /** @var ConverterInterface */
    private $converter;

    public function __construct()
    {
        $environment = Environment::createCommonMarkEnvironment();

        /**
         * @see https://commonmark.thephpleague.com/security/
         */
        $config = [
            'html_input' => Environment::HTML_INPUT_ALLOW,
            'allow_unsafe_links' => false,
            'max_nesting_level' => self::MAX_NESTING_LEVEL,
            'renderer' => [
                'soft_break' => "<br />\n",
            ],
        ];
        $this->converter = new CommonMarkConverter($config, $environment);
    }

    public function render(string $text): string
    {
        return $this->converter->convertToHtml($text);
    }
}
