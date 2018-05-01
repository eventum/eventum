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

use Eventum\RPC\RemoteApi;
use Eventum\RPC\XmlRpcServer;

require_once __DIR__ . '/../../init.php';

// close session
session_write_close();

$server = new XmlRpcServer(new RemoteApi());
$server->run();
