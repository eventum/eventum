<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.get_emails.php 1.15 04/01/19 15:19:26-00:00 jpradomaia $
//
set_time_limit(0);
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "db_access.php");
error_reporting(0);

$tpl = new Template_API();
$tpl->setTemplate("get_emails.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$tpl->displayTemplate();
flush();

$new_emails = 0;

$account = Email_Account::getDetails($HTTP_GET_VARS["ema_id"]);
$mbox = Support::connectEmailServer($account);
echo "<span class='default'>";
echo "<center><img src='" . APP_RELATIVE_URL . "images/uploading.gif'>&nbsp;<img src='" . APP_RELATIVE_URL . "images/computer.gif'>&nbsp;<img src='" . APP_RELATIVE_URL . "images/uploading.gif'></center><br />\n";
if ($mbox == false) {
    echo "<b>Error: Could not connect to the email server. Please verify your email account settings and try again.</b><br />";
    flush();
} else {
    echo "<b>Connected to the email server.</b><br /><br />\n";
    flush();
    $total_emails = Support::getTotalEmails($mbox);
    for ($i = 1; $i <= $total_emails; $i++) {
        flush();
        $email = Support::getEmailInfo($mbox, $account, $i);
        if (empty($email)) {
            continue;
        }
        flush();
        if (is_array($email)) {
            $new_emails++;
            $from = htmlspecialchars(Mime_Helper::fixEncoding($email['from']));
            echo "<b>Downloaded email</b> - '" . Mime_Helper::fixEncoding($email['subject']) . "' from " . trim($from) . ".<br />\n";
            echo '<script language="JavaScript">scrollBottom();</script>' . "\n";
            flush();
        } else {
            echo "<b>Skipping existing email</b> - " . Mime_Helper::fixEncoding($email) . "<br />\n";
            echo '<script language="JavaScript">scrollBottom();</script>' . "\n";
            flush();
        }
    }
    echo "<br /><br />";
    if ($new_emails == 0) {
        echo "<b>No new emails could be found.</b><br />";
        echo '<script language="JavaScript">scrollBottom();</script>' . "\n";
        flush();
    } else {
        echo "<b>Total of $new_emails emails were downloaded and added to the email database.</b><br />\n";
        echo '<script language="JavaScript">scrollBottom();</script>' . "\n";
        flush();
    }
}
echo "</span>";

?>
<script language="JavaScript">
<!--
<?php if ($new_emails != 0) : ?>
window.opener.location.href = 'emails.php';
<?php endif; ?>
window.setTimeout('window.close()', 3000);
//-->
</script>

</body>
</html>