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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('get_emails.tpl.html');

Auth::checkAuthentication(null, true);

$tpl->displayTemplate();
flush();

echo "<span class='default'>";
echo '<b>The following is the SQL statement produced by this reminder:</b><br /><br />';
echo nl2br(Reminder::getSQLQuery($_GET['rem_id'], $_GET['rma_id']));

?>

<br /><br />
<a class="link" href="javascript:window.close();">Close Window</a>

</span>

</body>
</html>
