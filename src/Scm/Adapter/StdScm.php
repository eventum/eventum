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
use Exception;
use Issue;
use SCM;

/**
 * Standard SCM handler
 *
 * @package Eventum\Scm
 */
class StdScm extends AbstractScmAdapter
{
    /** @var int[] */
    private $issues;

    /** @var string[] */
    private $files;

    /** @var string */
    private $commitid;

    /** @var string */
    private $commit_date;

    /** @var string[] */
    private $module;

    /** @var string */
    private $username;

    /** @var string */
    private $commit_msg;

    /** @var string */
    private $scm_name;

    /** @var string[] */
    private $old_versions;

    /** @var string[] */
    private $new_versions;

    /** @var bool */
    private $json;

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

        $this->commitid = $get->get('commitid');
        $this->commit_date = $get->get('commit_date');
        $this->scm_name = $get->get('scm_name');
        $this->module = $get->get('module');
        $this->username = $get->get('username');
        $this->issues = $get->get('issue');
        $this->commit_msg = $get->get('commit_msg');
        $this->files = $get->get('files');
        $this->old_versions = $get->get('old_versions');
        $this->new_versions = $get->get('new_versions');
        $this->json = $get->getBoolean('json');

        try {
            ob_start();
            $this->pingAction($this->commitid, $this->commit_date, $this->module, $this->username, $this->scm_name, $this->issues, $this->commit_msg);
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

        if ($this->json) {
            echo json_encode($status);
            exit;
        } else {
            echo $status['message'];
            exit($status['code']);
        }
    }

    /**
     * @param string $commitid
     * @param string $commit_date
     * @param string[] $module
     * @param string $username
     * @param string $scm_name
     * @param int[] $issues
     * @param string $commit_msg
     */
    private function pingAction($commitid, $commit_date, $module, $username, $scm_name, $issues, $commit_msg)
    {
        // module is per file (svn hook)
        if (is_array($module)) {
            $module = null;
        }

        $nfiles = count($this->files);
        $commit_time = $commit_date ? Date_Helper::convertDateGMT($commit_date) : Date_Helper::getCurrentDateGMT();

        // process checkins for each issue
        foreach ($issues as $issue_id) {
            // check early if issue exists to report proper message back
            // workflow needs to know project_id to find out which workflow class to use.
            $prj_id = Issue::getProjectID($issue_id);
            if (!$prj_id) {
                echo "issue #$issue_id not found\n";
                continue;
            }

            $files = array();
            for ($y = 0; $y < $nfiles; $y++) {
                $file = array(
                    'file' => $this->files[$y],
                    // version may be missing to indicate 'added' or 'removed' state
                    'old_version' => isset($this->old_versions[$y]) ? $this->old_versions[$y] : null,
                    'new_version' => isset($this->new_versions[$y]) ? $this->new_versions[$y] : null,
                    // there may be per file global (cvs) or module (svn)
                    'module' => isset($module) ? $module : $this->module[$y],
                );

                $files[] = $file;
            }

            SCM::addCheckins($issue_id, $commitid, $commit_time, $scm_name, $username, $commit_msg, $files);

            // print report to stdout of commits so hook could report status back to commiter
            $details = Issue::getDetails($issue_id);
            echo "#$issue_id - {$details['iss_summary']} ({$details['sta_title']})\n";
        }
    }
}
