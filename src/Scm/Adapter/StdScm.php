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
use Eventum\Model\Repository\CommitRepository;
use Exception;
use InvalidArgumentException;
use Issue;
use Symfony\Component\HttpFoundation\ParameterBag;
use Workflow;

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
                'code' => $code && is_numeric($code) ? $code : -1,
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
     * @param ParameterBag $params
     */
    private function processCommits(ParameterBag $params)
    {
        $issues = $this->getIssues($params);
        if (!$issues) {
            throw new InvalidArgumentException('No issues provided');
        }

        $cr = CommitRepository::create();

        $commitId = $params->get('commitid');
        $ci = Entity\Commit::create()->findOneByChangeset($commitId);

        // if ci already seen, skip adding commit and issue association
        // but still process commit files.
        // as cvs handler sends files in subdirs as separate requests
        if (!$ci) {
            $ci = $this->createCommit($params, $commitId);
            $cr->preCommit($ci, $params);
            $ci->save();

            // save issue association
            foreach ($issues as $issue_id) {
                Entity\IssueCommit::create()
                    ->setCommitId($ci->getId())
                    ->setIssueId($issue_id)
                    ->save();

                // print report to stdout of commits so hook could report status back to commiter
                $details = Issue::getDetails($issue_id);
                echo "#$issue_id - {$details['iss_summary']} ({$details['sta_title']})\n";
            }
        }

        // save commit files
        $files = $this->getFiles($params);
        foreach ($files as $file) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file['file'])
                ->setOldVersion($file['old_version'])
                ->setNewVersion($file['new_version']);
            $cf->save();
            $ci->addFile($cf);
        }

        foreach ($issues as $issue_id) {
            $cr->addCommit($issue_id, $ci);
        }
    }

    /**
     * @param ParameterBag $params
     * @param string $commitId
     * @return Entity\Commit
     */
    private function createCommit(ParameterBag $params, $commitId)
    {
        $ci = Entity\Commit::create()
            ->setScmName($params->get('scm_name'))
            ->setProjectName($params->get('project'))
            ->setCommitDate(Date_Helper::getDateTime($params->get('commit_date')))
            ->setMessage(trim($params->get('commit_msg')));

        // take username or author_name+author_email
        if ($params->get('username')) {
            $ci->setAuthorName($params->get('username'));
        } else {
            $ci
                ->setAuthorName($params->get('author_name'))
                ->setAuthorEmail($params->get('author_email'));
        }


        // set this last, as it may need other $ci properties

        $ci->setChangeset($commitId ?: $this->generateCommitId($ci));

        return $ci;
    }

    /**
     * Get files in sane format
     */
    private function getFiles(ParameterBag $params)
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
            $issues[] = $issue_id;
        }

        return $issues;
    }

    /**
     * Seconds to allow commit date to differ to consider them as same commit id
     */
    const COMMIT_TIME_DRIFT = 10;

    /**
     * Generate commit id
     *
     * @param Entity\Commit $ci
     * @return string
     */
    private function generateCommitId(Entity\Commit $ci)
    {
        $seed = array(
            $ci->getCommitDate()->getTimestamp() / self::COMMIT_TIME_DRIFT,
            $ci->getAuthorName(),
            $ci->getAuthorEmail(),
            $ci->getMessage(),
        );
        $checksum = md5(implode('', $seed));

        // CVS commitid is 16 byte length base62 encoded random and seems always end with z0
        // so we use 14 bytes from md5, and z1 suffix to get similar (but not conflicting) commitid
        return substr($checksum, 1, 14) . 'z1';
    }
}
