<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Defect Tracking System                                     |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Joao Prado Maia                                   |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@impleo.net>                            |
// +----------------------------------------------------------------------+
//
// @(#) $Id: $
//

include_once("../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_PEAR_PATH . 'Net/SmartIRC.php');

// SETUP: need to change the project name in here
$channels = array(
    Project::getID('Default Project') => array(
        '#issues',
    )
);

class Eventum_Bot
{
    /**
     * Helper method to get the list of channels that should be used in the
     * notifications
     *
     * @access  private
     * @param   integer $prj_id The project ID
     * @return  array The list of channels
     */
    function _getChannels($prj_id)
    {
        global $channels;
        return $channels[$prj_id];
    }


    /**
     * Method used as a callback to send notification events to the proper
     * recipients.
     *
     * @access  public
     * @param   resource $irc The IRC connection handle
     * @return  void
     */
    function notifyEvents(&$irc)
    {
        // check the message table
        $stmt = "SELECT
                    ino_id,
                    ino_iss_id,
                    iss_prj_id,
                    ino_message
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "irc_notice
                 WHERE
                    iss_id=ino_iss_id AND
                    ino_status='pending'";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        for ($i = 0; $i < count($res); $i++) {
            $channels = $this->_getChannels($res[$i]['iss_prj_id']);
            if (count($channels) > 0) {
                foreach ($channels as $channel) {
                    $res[$i]['ino_message'] .= ' - ' . APP_BASE_URL . 'view.php?id=' . $res[$i]['ino_iss_id'];
                    $this->sendResponse($irc, $channel, $res[$i]['ino_message']);
                }
                // mark message as sent
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "irc_notice
                         SET
                            ino_status='sent'
                         WHERE
                            ino_id=" . $res[$i]['ino_id'];
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
        }
    }


    /**
     * Method used to send a message to the given target.
     *
     * @access  public
     * @param   resource $irc The IRC connection handle
     * @param   string $target The target for this message
     * @param   string $response The message to send
     * @return  void
     */
    function sendResponse(&$irc, $target, $response)
    {
        // XXX: need way to handle messages with length bigger than 255 chars
        if (!is_array($response)) {
            $response = array($response);
        }
        foreach ($response as $line) {
            if (substr($target, 0, 1) != '#') {
                $type = SMARTIRC_TYPE_QUERY;
            } else {
                $type = SMARTIRC_TYPE_CHANNEL;
            }
            $irc->message($type, $target, $response, SMARTIRC_CRITICAL);
            sleep(1);
        }
    }
}

$bot = &new Eventum_Bot();
$irc = &new Net_SmartIRC();
$irc->setDebug(SMARTIRC_DEBUG_ALL);
$irc->setLogdestination(SMARTIRC_FILE);
$irc->setLogfile('irclog.txt');
$irc->setUseSockets(TRUE);

// register saytime() to be called every 30 sec. (30,000 milliseconds)
$irc->registerTimehandler(3000, $bot, 'notifyEvents');

$irc->connect('localhost', 6667);
$irc->login('EventumBOT', 'EventumBOT', 0, 'EventumBOT');
foreach ($channels as $prj_id => $channel_list) {
    $irc->join($channel_list);
}
$irc->listen();
$irc->disconnect();
?>