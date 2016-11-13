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

require_once 'XML/RPC.php';
require_once 'Eventum_RPC.php';

$client = new Eventum_RPC();
$client->setURL('http://rabbit.impleo.net/rpc/xmlrpc.php');
$client->setCredentials('user', 'password');
$client->setDebug(true);
$id = 64;

try {
    $result = $client->getIssueDetails((int) $id);
} catch (Eventum_RPC_Exception $e) {
    echo $e->getMessage(), "\n";
    echo $e->getTraceAsString(), "\n";
    exit(1);
}

print_r($result);
