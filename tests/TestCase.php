<?php

class TestCase extends PHPUnit_Framework_TestCase
{
    public static function skipTravis($message = 'Disabled in Travis')
    {
        if (getenv('TRAVIS')) {
            self::markTestSkipped($message);
        }
    }

    public static function skipJenkins($message = 'Disabled Jenkins')
    {
        if (getenv('JENKINS_HOME')) {
            self::markTestSkipped($message);
        }
    }

    public static function skipCi($message = 'Disabled in Travis/Jenkins')
    {
        if (getenv('TRAVIS') || getenv('JENKINS_HOME')) {
            self::markTestSkipped($message);
        }
    }
}