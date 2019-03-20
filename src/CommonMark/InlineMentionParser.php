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

namespace Eventum\CommonMark;

use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

final class InlineMentionParser implements InlineParserInterface
{
    /** @var string */
    private $linkPattern;

    /** @var string */
    private $handleRegex = '/^[A-Za-z0-9_]+(?!\w)/';

    /**
     * @param string $linkPattern
     */
    public function __construct(string $linkPattern)
    {
        $this->linkPattern = $linkPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'mention';
    }

    /**
     * {@inheritdoc}
     */
    public function getCharacters(): array
    {
        return ['@'];
    }

    /**
     * {@inheritdoc}
     */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();

        // The @ symbol must not have any other characters immediately prior
        $previousChar = $cursor->peek(-1);
        if ($previousChar !== null && $previousChar !== ' ') {
            // peek() doesn't modify the cursor, so no need to restore state first
            return false;
        }

        // Save the cursor state in case we need to rewind and bail
        $previousState = $cursor->saveState();

        // Advance past the @ symbol to keep parsing simpler
        $cursor->advance();

        // Parse the handle
        $handle = $cursor->match($this->handleRegex);
        if (empty($handle)) {
            // Regex failed to match; this isn't a valid Twitter handle
            $cursor->restoreState($previousState);

            return false;
        }

        $url = sprintf($this->linkPattern, $handle);

        $inlineContext->getContainer()->appendChild(new Link($url, '@' . $handle));

        return true;
    }
}
