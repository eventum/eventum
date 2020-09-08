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

use League\CommonMark\EnvironmentInterface;

interface MarkdownRendererInterface
{
    public const RENDER_BLOCK = self::class . '::block';
    public const RENDER_INLINE = self::class . '::inline';

    public function render(string $text): string;
    public function getEnvironment(): EnvironmentInterface;
}
