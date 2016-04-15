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

namespace Eventum\Model\Entity;

use Date_Helper;
use Issue;
use Symfony\Component\HttpFoundation\ParameterBag;

class StdScmPayload
{
    private $params;

    public function __construct(ParameterBag $params)
    {
        $this->params = $params;
    }

    /**
     * Get branch the commit was made on
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->params->get('branch');
    }

    /**
     * @return string
     */
    public function getCommitId()
    {
        return $this->params->get('commitid');
    }

    /**
     * Get issue id's, validate that they exist, because workflow needs project id
     *
     * @return array issue ids
     */
    public function getIssues()
    {
        $issues = array();
        // check early if issue exists to report proper message back
        // workflow needs to know project_id to find out which workflow class to use.
        foreach ($this->params->get('issue') as $issue_id) {
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
     * @return Commit
     */
    public function createCommit()
    {
        $params = $this->params;
        $ci = Commit::create()
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

    /**
     * Get files in sane format
     */
    public function getFiles()
    {
        $params = $this->params;
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
}
