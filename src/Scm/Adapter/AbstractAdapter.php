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

use Issue;
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
     * parse the commit message and get all issue numbers we can find
     *
     * @param string $commit_msg
     * @return array
     */
    protected function match_issues($commit_msg)
    {
        preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $commit_msg, $matches);

        if (count($matches[1]) > 0) {
            return $matches[1];
        }

        return null;
    }
}
