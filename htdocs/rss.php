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

require_once dirname(__FILE__) . '/../init.php';

$setup = Setup::load();
if (empty($setup['tool_caption'])) {
    $setup['tool_caption'] = APP_NAME;
}

function sendAuthenticateHeader() {
    global $setup;

    // FIXME: escape tool_caption properly
    header('WWW-Authenticate: Basic realm="' . $setup['tool_caption'] . '"');
    header('HTTP/1.0 401 Unauthorized');
}

function returnError($msg) {
    header("Content-Type: text/xml");
    echo '<?xml version="1.0"?>' . "\n";
?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:admin="http://webns.net/mvcb/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>Error!</title>
    <link><?php echo APP_BASE_URL; ?></link>
    <description><?php echo htmlspecialchars($msg); ?></description>
  </channel>
</rss>
<?php
}

/**
 * Extract HTTP Authorization data from HTTP headers
 * @return array($username, $password)
 */
function getAuthData() {
    // Extra tweak needed for IIS/ISAPI users since the PHP_AUTH_USER/PW variables are
    // not set on that particular platform. Instead what you get is a base64 encoded
    // value of the username:password under HTTP_AUTHORIZATION
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
    }

    if ((!empty($_SERVER['ALL_HTTP'])) && (strstr($_SERVER['ALL_HTTP'], 'HTTP_AUTHORIZATION'))) {
        preg_match('/HTTP_AUTHORIZATION:Basic (.*)/', $_SERVER['ALL_HTTP'], $matches);
        if (count($matches) > 0) {
            return explode(':', base64_decode($matches[1]));
        }
    }

    return null;
}

/**
 * Authorize request.
 * @return authorized username (email). exits program if failed
 * TODO: translations
 * TODO: ip based control
 */
function authorizeRequest() {
    $authData = getAuthData();
    if ($authData === null) {
        sendAuthenticateHeader();
        echo 'Error: You are required to authenticate in order to access the requested RSS feed.';
        exit;
    }

    list($authUser, $authPassword) = $authData;
    $usr_id = User::getUserIDByEmail($authUser);

    // check the authentication
    if (Validation::isWhitespace($authUser)) {
        sendAuthenticateHeader();
        echo 'Error: Please provide your email address.';
        exit;
    }
    if (Validation::isWhitespace($authPassword)) {
        sendAuthenticateHeader();
        echo 'Error: Please provide your password.';
        exit;
    }
    // check if user exists
    if (!Auth::userExists($authUser)) {
        sendAuthenticateHeader();
        echo 'Error: The user specified does not exist.';
        exit;
    }
    // check if the password matches
    if (!Auth::isCorrectPassword($authUser, $authPassword)) {
        sendAuthenticateHeader();
        echo 'Error: The provided email address/password combo is not correct.';
        exit;
    }
    // check if this user did already confirm his account
    if (Auth::isPendingUser($authUser)) {
        sendAuthenticateHeader();
        echo 'Error: The provided user still needs to have its account confirmed.';
        exit;
    }
    // check if this user is really an active one
    if (!Auth::isActiveUser($authUser)) {
        sendAuthenticateHeader();
        echo 'Error: The provided user is currently set as an inactive user.';
        exit;
    }

    // check if the required parameter 'custom_id' is really being passed
    if (empty($_GET['custom_id'])) {
        returnError("Error: The required 'custom_id' parameter was not provided.");
        exit;
    }

    $usr_id = User::getUserIDByEmail($authUser);
    // check if the passed 'custom_id' parameter is associated with the usr_id
    if ((!Filter::isGlobal($_GET['custom_id'])) && (!Filter::isOwner($_GET['custom_id'], $usr_id))) {
        returnError('Error: The provided custom filter ID is not associated with the given email address.');
        exit;
    }

    return $authUser;
}

$authUser = authorizeRequest();

$filter = Filter::getDetails($_GET["custom_id"], FALSE);

Auth::createFakeCookie(User::getUserIDByEmail($authUser), $filter['cst_prj_id']);

$options = array(
    'users'         => $filter['cst_users'],
    'keywords'      => $filter['cst_keywords'],
    'priority'      => $filter['cst_iss_pri_id'],
    'category'      => $filter['cst_iss_prc_id'],
    'status'        => $filter['cst_iss_sta_id'],
    'hide_closed'   => $filter['cst_hide_closed'],
    'sort_by'       => $filter['cst_sort_by'],
    'sort_order'    => $filter['cst_sort_order'],
    'custom_field'  => $filter['cst_custom_field'],
    'search_type'   => $filter['cst_search_type']
);
$issues = Search::getListing($filter['cst_prj_id'], $options, 0, 'ALL', TRUE);
$issues = $issues['list'];
$project_title = Project::getName($filter['cst_prj_id']);
Issue::getDescriptionByIssues($issues);

# TODO: reporter is not present

Header("Content-Type: text/xml; charset=" . APP_CHARSET);
echo '<?xml version="1.0" encoding="'. APP_CHARSET .'"?>' . "\n";
?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:admin="http://webns.net/mvcb/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title><?php echo htmlspecialchars($setup['tool_caption']); ?>
    - <?php echo htmlspecialchars($filter['cst_title']); ?></title>
    <link><?php echo APP_BASE_URL; ?></link>
    <description>List of issues</description>
<?php foreach($issues as $issue) { ?>
    <item>
      <title><?php echo '#' . $issue['iss_id'] . " - " . htmlspecialchars($issue['iss_summary']); ?></title>
      <link><?php echo APP_BASE_URL . "view.php?id=" . $issue['iss_id']; ?></link>
      <description>
      Project: <?php echo htmlspecialchars($project_title); ?>&lt;BR&gt;&lt;BR&gt;
      Assignment: <?php echo htmlspecialchars($issue['assigned_users']); ?>&lt;BR&gt;
      Status: <?php echo htmlspecialchars($issue['sta_title']); ?>&lt;BR&gt;
      Priority: <?php echo htmlspecialchars($issue['pri_title']); ?>&lt;BR&gt;
      Category: <?php echo htmlspecialchars($issue['prc_title']); ?>&lt;BR&gt;
      &lt;BR&gt;<?php echo htmlspecialchars(Link_Filter::activateLinks(nl2br($issue['iss_description']))); ?>&lt;BR&gt;
      </description>
      <author><?php echo htmlspecialchars($issue['reporter']); ?></author>
      <pubDate><?php echo Date_Helper::getRFC822Date($issue['iss_created_date'], "GMT"); ?></pubDate>
    </item>
<?php } ?>

  </channel>
</rss>
