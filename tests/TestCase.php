<?php

class TestCase extends PHPUnit_Framework_TestCase
{
    public function skipTravis($message = 'Disabled in Travis')
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped($message);
        }
    }

    public function skipCi($message = 'Disabled in Travis/Jenkins')
    {
        if (getenv('TRAVIS') || getenv('JENKINS_HOME')) {
            $this->markTestSkipped($message);
        }
    }
}