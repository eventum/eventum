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

use League\HTMLToMarkdown\HtmlConverter as LeagueHtmlConverter;
use League\HTMLToMarkdown\HtmlConverterInterface;

final class HtmlConverter
{
    /** @var HtmlConverterInterface */
    private $converter;

    public function __construct()
    {
        $options = [
            'remove_nodes' => 'head style meta',
            'strip_tags' => true,
        ];

        $this->converter = new LeagueHtmlConverter($options);
    }

    public function convert(string $html): string
    {
        return $this->converter->convert($html);
    }
}
