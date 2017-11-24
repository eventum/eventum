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

namespace Eventum\Scm\Payload;

use Date_Helper;
use Eventum\Model\Entity\Commit;
use Issue;
use Symfony\Component\HttpFoundation\ParameterBag;

class StandardPayload implements PayloadInterface
{
    private $params;

    public function __construct(array $payload)
    {
        $this->params = new ParameterBag($payload);
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
        $issues = [];
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
        $ci = (new Commit())
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
     * Get files associated with the commit
     *
     * @return array
     */
    public function getFiles()
    {
        // create array with predefined keys
        $files = [
            'added',
            'removed',
            'modified',
        ];
        $files = array_fill_keys($files, []);

        return $this->params->get('files') + $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return $this->params->all();
    }
}
