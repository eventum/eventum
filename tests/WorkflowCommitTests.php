<?php

require_once 'TestWorkflow.php';

class CommitTestWorkflow extends TestWorkflow {
    function handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg) {
    	error_log("handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg)");
    }
}

class WorkflowCommitTests extends WorkflowTest {
	protected $workflow_class = 'CommitTestWorkflow';

	/**
	 * @test
	 * @dataProvider commitData
	 */
	public function scm_commands($prj_id, $issue_id, $module, $files, $username, $commit_msg) {
		$this->workflow->handleSCMcheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
	}

	public function commitData() {
		$files = array();
		return array(
			array(1, 1, 'test', $files, 'test', 'test'),
		);
	}
}
