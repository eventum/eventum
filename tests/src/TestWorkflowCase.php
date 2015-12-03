<?php

class WorkflowTestCase extends TestCase {
	protected $workflow_class = 'TestWorkflow';

	/**
	 * setup workflow object
	 */
	public function setUp() {
		$classname = $this->workflow_class;
		$this->workflow = new $classname();
	}
}
