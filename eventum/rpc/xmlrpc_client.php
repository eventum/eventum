<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Joуo Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.xmlrpc_client.php 1.3 03/01/16 01:47:32-00:00 jpm $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "db_access.php");

include_once(APP_PEAR_PATH . "XML_RPC/RPC.php");

$client = new XML_RPC_Client("/rpc/xmlrpc.php", "rabbit.impleo.net", 80);
$client->setDebug(true);
$params = array(
    new XML_RPC_Value(5, "int"),
    new XML_RPC_Value("Testando pelo XML-RPC", "string"),
    new XML_RPC_Value("descriчуo iria aqui", "string")
);
$msg = new XML_RPC_Message("addIssue", $params);
$result = $client->send($msg);
var_dump($result);
?>