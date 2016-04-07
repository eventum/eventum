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
use Eventum\Model\Entity\Commit;
use Eventum\Model\Entity\CommitFile;
use Eventum\Model\Entity\IssueCommit;
use Exception;
use InvalidArgumentException;
use Issue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Standard SCM handler
 *
 * @package Eventum\Scm
 */
class StdScm extends AbstractScmAdapter
{
    /**
     * @inheritdoc
     */
    public function can()
    {
        // require at least 'issue' GET parameter
        return $this->request->query->has('issue');
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        $get = $this->request->query;
        $json = $get->getBoolean('json');

        try {
            ob_start();
            $this->processCommits($get);
            $status = array(
                'code' => 0,
                'message' => ob_get_clean(),
            );
        } catch (Exception $e) {
            $code = $e->getCode();
            $status = array(
                'code' => $code ? $code : -1,
                'message' => $e->getMessage(),
            );
            $this->log->error($e);
        }

        if ($json) {
            echo json_encode($status);
            exit;
        } else {
            echo $status['message'];
            exit($status['code']);
        }
    }

    /**
     * TODO: workflow method to resolve 'username' to name and email
     *
     * @param ParameterBag $params
     */
    private function processCommits(ParameterBag $params)
    {
        $issues = $this->getIssues($params);
        if (!$issues) {
            throw new InvalidArgumentException('No issues provided');
        }

        // save commit
        $ci = Commit::create()
            ->setScmName($params->get('scm_name'))
            ->setCommitId($params->get('commitid'))
            ->setAuthorName($params->get('username'))
            ->setCommitDate(Date_Helper::getDateTime($params->get('commit_date')))
            ->setMessage(trim($params->get('commit_msg')));

        $ci->save();

        // save commit files
        $files = $this->getFiles($params);
        foreach ($files as $file) {
            CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file['file'])
                ->setOldVersion($file['old_version'])
                ->setNewVersion($file['new_version'])
                ->setProjectName($file['module'])
                ->save();
        }

        // save issue association
        foreach ($issues as $issue_id) {
            $ci = IssueCommit::create()
                ->setCommitId($ci->getId())
                ->setIssueId($issue_id)
                ->save();

            // print report to stdout of commits so hook could report status back to commiter
            $details = Issue::getDetails($issue_id);
            echo "#$issue_id - {$details['iss_summary']} ({$details['sta_title']})\n";
        }
    }

    /**
     * Get files in sane format
     */
    private function getFiles(ParameterBag $params)
    {
        $files = $params->get('files');
        $old_versions = $params->get('old_versions');
        $new_versions = $params->get('new_versions');
        $modules = $params->get('module');

        $nfiles = count($files);
        $files = array();
        for ($y = 0; $y < $nfiles; $y++) {
            $file = array(
                // there may be per file global (cvs) or module (svn)
                'module' => is_array($modules) ? $modules[$y] : $modules,
                'file' => $files[$y],
                // for CVS version may be missing to indicate 'added' or 'removed' state
                // for SVN/Git there's no per file revisions
                'old_version' => isset($old_versions[$y]) ? $old_versions[$y] : null,
                'new_version' => isset($new_versions[$y]) ? $new_versions[$y] : null,
            );

            $files[] = $file;
        }

        return $files;
    }

    /**
     * Get issue id's, validate that they exist, because workflow needs project id
     *
     * @param ParameterBag $params
     * @return array issue ids
     */
    private function getIssues(ParameterBag $params)
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
            $issue_id[] = $issue_id;
        }

        return $issues;
    }
}
