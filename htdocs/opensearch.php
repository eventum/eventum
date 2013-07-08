<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009 Sun Microsystem Inc.                              |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//

require_once dirname(__FILE__) . '/../init.php';

// Render OpenSearch description document (OSDD), http://www.opensearch.org/
if (!Auth::hasValidCookie(APP_COOKIE)) {
	Header('HTTP/1.0 403 Forbidden');
	exit(0);
}

Header("Content-Type: text/xml; charset=" . APP_CHARSET);

$tpl = new Template_Helper();
$tpl->assign("app_charset", APP_CHARSET);
$tpl->setTemplate("opensearch.tpl.xml");
$tpl->displayTemplate();
