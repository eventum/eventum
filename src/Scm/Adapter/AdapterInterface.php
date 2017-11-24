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

interface AdapterInterface
{
    public function __construct(Request $request, Logger $logger);

    /**
     * Return true if this adapter can process
     *
     * @return bool
     */
    public function can();

    /**
     * Process the request
     */
    public function process();
}
