<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                              |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: xmlrpc_client.php 3206 2007-01-24 20:24:35Z glen $
//
require_once(dirname(__FILE__) . "/../init.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "db_access.php");

require_once(APP_PEAR_PATH . "XML_RPC/RPC.php");

$client = new XML_RPC_Client("/rpc/xmlrpc.php", "rabbit.impleo.net", 80);
$client->setDebug(true);
$params = array(
    new XML_RPC_Value(5, "int"),
    new XML_RPC_Value("Testando pelo XML-RPC", "string"),
    new XML_RPC_Value("descrição iria aqui", "string")
);
$msg = new XML_RPC_Message("addIssue", $params);
$result = $client->send($msg);
var_dump($result);
