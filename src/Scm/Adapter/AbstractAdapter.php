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

use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractAdapter implements AdapterInterface
{
    /** @var Request */
    protected $request;

    /** @var Logger */
    protected $log;

    public function __construct(Request $request, Logger $logger)
    {
        $this->request = $request;
        $this->log = $logger;
    }
}
