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
// @(#) $Id$
//


class Draft
{
    /**
     * Method used to save the draft response in the database for 
     * further use.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function saveEmail($issue_id, $to, $cc, $subject, $message, $parent_id = FALSE)
    {
        if (empty($parent_id)) {
            $parent_id = 'NULL';
        }
        $usr_id = Auth::getUserID();
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft
                 (
                    emd_updated_date,
                    emd_usr_id,
                    emd_iss_id,
                    emd_sup_id,
                    emd_subject,
                    emd_body
                 ) VALUES (
                    NOW(),
                    $usr_id,
                    $issue_id,
                    $parent_id,
                    '$subject',
                    '$message'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_emd_id = $GLOBALS['db_api']->get_last_insert_id();
            Draft::addEmailRecipient($new_emd_id, $to, false);
            $cc = str_replace(',', ';', $cc);
            $ccs = explode(';', $cc);
            foreach ($ccs as $cc) {
                Draft::addEmailRecipient($new_emd_id, $cc, true);
            }
            Issue::markAsUpdated($issue_id);
            History::add($issue_id, 'Email message saved as a draft by ' . User::getFullName($usr_id));
            return 1;
        }
    }


    function update($issue_id, $emd_id, $to, $cc, $subject, $message, $parent_id = FALSE)
    {
        if (empty($parent_id)) {
            $parent_id = 'NULL';
        }
        $usr_id = Auth::getUserID();
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft
                 SET
                    emd_updated_date=NOW(),
                    emd_usr_id=$usr_id,
                    emd_iss_id=$issue_id,
                    emd_sup_id=$parent_id,
                    emd_subject='" . Misc::runSlashes($subject) . "',
                    emd_body='" . Misc::runSlashes($message) . "'
                 WHERE
                    emd_id=$emd_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Draft::removeRecipients($emd_id);
            Draft::addEmailRecipient($emd_id, $to, false);
            $cc = str_replace(',', ';', $cc);
            $ccs = explode(';', $cc);
            foreach ($ccs as $cc) {
                Draft::addEmailRecipient($emd_id, $cc, true);
            }
            Issue::markAsUpdated($issue_id);
            History::add($issue_id, 'Email message draft updated by ' . User::getFullName($usr_id));
            return 1;
        }
    }


    function remove($emd_id)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft
                 WHERE
                    emd_id=$emd_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Draft::removeRecipients($emd_id);
            return true;
        }
    }


    function removeRecipients($emd_id)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft_recipient
                 WHERE
                    edr_emd_id=$emd_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    function addEmailRecipient($emd_id, $email, $is_cc)
    {
        if (!$is_cc) {
            $is_cc = 0;
        } else {
            $is_cc = 1;
        }
        $email = trim($email);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft_recipient
                 (
                    edr_emd_id,
                    edr_is_cc,
                    edr_email
                 ) VALUES (
                    $emd_id,
                    $is_cc,
                    '" . Misc::runSlashes($email) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    function getDetails($emd_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft
                 WHERE
                    emd_id=$emd_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            list($res['to'], $res['cc']) = Draft::getEmailRecipients($emd_id);
            return $res;
        }
    }


    function getList($issue_id)
    {
        $stmt = "SELECT
                    emd_id,
                    emd_usr_id,
                    emd_subject,
                    emd_updated_date
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft
                 WHERE
                    emd_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['from'] = User::getFromHeader($res[$i]['emd_usr_id']);
                list($res[$i]['to'], ) = Draft::getEmailRecipients($res[$i]['emd_id']);
            }
            return $res;
        }
    }


    function getEmailRecipients($emd_id)
    {
        $stmt = "SELECT
                    edr_email,
                    edr_is_cc
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft_recipient
                 WHERE
                    edr_emd_id=$emd_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array('', '');
        } else {
            $to = '';
            $ccs = array();
            foreach ($res as $email => $is_cc) {
                if ($is_cc) {
                    $ccs[] = $email;
                } else {
                    $to = $email;
                }
            }
            return array(
                $to,
                $ccs
            );
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Draft Class');
}
?>