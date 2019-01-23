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

namespace Eventum\Scm\Adapter;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAdapter implements AdapterInterface
{
    /** @var Request */
    protected $request;

    /** @var Logger */
    protected $log;

    public function __construct(Request $request, Logger $logger)
    {
        $this->request = $request;
        $this->log = $logger;
    }

    /**
     * Parse the commit message and get all issue numbers we can find
     */
    protected function matchIssueIds(string $message): ?array
    {
        preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $message, $matches);

        if (count($matches[1]) > 0) {
            return $matches[1];
        }

        return null;
    }

    protected function matchIssueLinks(string $message): ?array
    {
        $base_url = preg_quote(APP_BASE_URL, '/');

        $regexp = "/
            (?P<issue_match>(?i:issue):?\s\#?(?P<issue_id>\d+)) |
            (?P<url_match>{$base_url}view\.php\?id=(?P<url_issue_id>\d+))
        /x";

        preg_match_all($regexp, $message, $matches);

        if (count($matches[1]) > 0) {
            return $matches[1];
        }

        return null;
    }
}
