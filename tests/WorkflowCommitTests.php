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

class CommitTestWorkflow extends TestWorkflow
{
    public function handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg)
    {
        error_log("handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg)");
    }
}

class WorkflowCommitTestsCase extends WorkflowTestCase
{
    protected $workflow_class = 'CommitTestWorkflow';

    /**
     * @test
     * @dataProvider commitData
     */
    public function scm_commands($prj_id, $issue_id, $module, $files, $username, $commit_msg)
    {
        $this->workflow->handleSCMcheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
    }

    public function commitData()
    {
        $files = [];

        return [
            [1, 1, 'test', $files, 'test', 'test'],
        ];
    }
}
