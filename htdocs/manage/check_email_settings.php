<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("get_emails.tpl.html");

Auth::checkAuthentication(APP_COOKIE, NULL, true);

$tpl->displayTemplate();
flush();

echo "<span class='default'>";

// we need the IMAP extension for this to work
if (!function_exists('imap_open')) {
    echo "<b>Error: Eventum requires the IMAP extension in order to connect to IMAP/POP3 servers.<br /><br />";
    echo "Please refer to the PHP manual for more details about how to enable the IMAP extension.</b>";
} else {
    // check if the hostname is just an IP based one
    if ((!preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $_POST["hostname"])) &&
            (gethostbyname($_POST["hostname"]) == $_POST["hostname"])) {
        echo "<b>The provided hostname could not be resolved. Please check your information and try again.</b>";
    } else {
        $account = array(
            "ema_hostname" => $_POST["hostname"],
            "ema_port"     => $_POST["port"],
            "ema_type"     => $_POST["type"],
            "ema_folder"   => $_POST["folder"],
            "ema_username" => $_POST["username"],
            "ema_password" => $_POST["password"]
        );
        $mbox = Support::connectEmailServer($account);
        if (!$mbox) {
            echo "<b>Could not connect to the server with the provided information.</b>";
        } else {
            echo "<b>Thank you, the connection to the email server was created successfully.</b>";
        }
    }
}
?>

<br /><br />
<a class="link" href="javascript:window.close();">Close Window</a>

</span>

</body>
</html>
