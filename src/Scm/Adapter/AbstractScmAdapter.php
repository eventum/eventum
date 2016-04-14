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

use Date_Helper;
use Eventum\Model\Entity;
use Issue;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractScmAdapter implements ScmInterface
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

    /**
     * Get issue id's, validate that they exist, because workflow needs project id
     *
     * @param ParameterBag $params
     * @return array issue ids
     */
    protected function getIssues(ParameterBag $params)
    {
        $issues = array();
        // check early if issue exists to report proper message back
        // workflow needs to know project_id to find out which workflow class to use.
        foreach ($params->get('issue') as $issue_id) {
            $prj_id = Issue::getProjectID($issue_id);
            if (!$prj_id) {
                echo "issue #$issue_id not found\n";
                continue;
            }
            $issues[] = $issue_id;
        }

        return $issues;
    }

    /**
     * Get files in sane format
     */
    protected function getFiles(ParameterBag $params)
    {
        $files = $params->get('files');
        $old_versions = $params->get('old_versions');
        $new_versions = $params->get('new_versions');

        $nfiles = count($files);
        $res = array();
        for ($y = 0; $y < $nfiles; $y++) {
            $file = array(
                'file' => $files[$y],
                // for CVS version may be missing to indicate 'added' or 'removed' state
                // for SVN/Git there's no per file revisions
                'old_version' => isset($old_versions[$y]) ? $old_versions[$y] : null,
                'new_version' => isset($new_versions[$y]) ? $new_versions[$y] : null,
            );

            $res[] = $file;
        }

        return $res;
    }

    /**
     * @param ParameterBag $params
     * @return Entity\Commit
     */
    protected function createCommit(ParameterBag $params)
    {
        $ci = Entity\Commit::create()
            ->setScmName($params->get('scm_name'))
            ->setProjectName($params->get('project'))
            ->setCommitDate(Date_Helper::getDateTime($params->get('commit_date')))
            ->setBranch($params->get('branch'))
            ->setMessage(trim($params->get('commit_msg')));

        // take username or author_name+author_email
        if ($params->get('username')) {
            $ci->setAuthorName($params->get('username'));
        } else {
            $ci
                ->setAuthorName($params->get('author_name'))
                ->setAuthorEmail($params->get('author_email'));
        }

        return $ci;
    }
}
