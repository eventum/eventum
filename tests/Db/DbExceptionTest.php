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

namespace Eventum\Test\Db;

use Eventum\Db\DatabaseException;
use Eventum\Test\TestCase;
use PDOException;
use ReflectionClass;

class DbExceptionTest extends TestCase
{
    public function testException()
    {
        $message = "SQLSTATE[HY000]: General error: 144 Table './eventum/mail_queue' is marked as crashed and last (automatic?) repair failed";
        $code = 'HY000';

        $e = $this->createException(PDOException::class, $message, $code);
        $this->assertEquals($message, $e->getMessage());
        $this->assertEquals($code, $e->getCode());

        $ex = new DatabaseException($e->getMessage(), $e->getCode(), $e);
        $this->assertEquals($message, $ex->getMessage());
        $this->assertEquals($code, $ex->getCode());
    }

    /**
     * PDOException code is non-numeric
     * and it's impossible to construct such object
     * because it expects integer (PDOException extends Exception).
     *
     * Fill the value with Reflection.
     *
     * @param string $class
     * @param string $message
     * @param string $code
     * @return object
     */
    private function createException($class, $message, $code)
    {
        $e = new $class($message);
        $class = new ReflectionClass($e);
        $property = $class->getProperty('code');
        $property->setAccessible(true);
        $property->setValue($e, $code);

        return $e;
    }
}
