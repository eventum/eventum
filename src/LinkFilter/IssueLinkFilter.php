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

namespace Eventum\LinkFilter;

use Issue;

/**
 * Linkfilter to Link Eventum issue id's
 */
class IssueLinkFilter implements LinkFilterInterface
{
    /** @var string */
    private $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getPatterns(): array
    {
        return [
            '/(?P<match>issue:?\s\#?(?P<issue_id>\d+))/i',
            # lookbehind here avoid matching "open http:// in new window" and href="http://"
            "#(?<!open |href=\"){$this->baseUrl}view\.php\?id=(?P<issue_id>\d+)#",
        ];
    }

    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    public function __invoke(array $matches): string
    {
        $issue_id = $matches['issue_id'];
        // check if the issue is still open
        if (Issue::isClosed($issue_id)) {
            $class = 'closed';
        } else {
            $class = '';
        }
        $issue_title = Issue::getTitle($issue_id);
        $link_title = htmlspecialchars("issue {$issue_id} - {$issue_title}");

        // use named capture 'match' if present
        $match = $matches['match'] ?? "issue {$issue_id}";

        return "<a title=\"{$link_title}\" class=\"{$class}\" href=\"view.php?id={$matches['issue_id']}\">{$match}</a>";
    }
}
