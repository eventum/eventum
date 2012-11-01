<?php

require_once 'TestSetup.php';

class TestWorkflow extends Abstract_Workflow_Backend {
}

class WorkflowTest extends PHPUnit_Framework_TestCase {
	protected $workflow_class = 'TestWorkflow';

	/**
	 * setup workflow object
	 */
	public function setUp() {
		$classname = $this->workflow_class;
		$this->workflow = new $classname();
	}
}
