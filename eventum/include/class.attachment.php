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
// @(#) $Id: s.class.attachment.php 1.28 04/01/10 02:04:42-00:00 jpradomaia $
//


/**
 * Class designed to handle all business logic related to attachments being
 * uploaded to issues in the application.
 *
 * @author  João Prado Maia <jpm@mysql.com>
 * @version $Revision: 1.28 $
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.issue.php");

class Attachment
{
    /**
     * Method used to remove a specific file out of an existing attachment.
     *
     * @access  public
     * @param   integer $iaf_id The attachment file ID
     * @return  -1 or -2 if the removal was not successful, 1 otherwise
     */
    function removeIndividualFile($iaf_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = "SELECT
                    iat_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                 WHERE
                    iaf_id=$iaf_id AND
                    iat_id=iaf_iat_id AND
                    iat_usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (empty($res)) {
                return -2;
            } else {
                // check if the file is the only one in the attachment
                $stmt = "SELECT
                            iat_id
                         FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment,
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                         WHERE
                            iaf_id=$iaf_id AND
                            iaf_iat_id=iat_id";
                $attachment_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

                $res = Attachment::getFileList($attachment_id);
                if (@count($res) > 1) {
                    Attachment::removeFile($iaf_id);
                } else {
                    Attachment::remove($attachment_id);
                }
                return 1;
            }
        }
    }


    /**
     * Method used to return the details for a given attachment.
     *
     * @access  public
     * @param   integer $file_id The attachment ID
     * @return  array The details of the attachment
     */
    function getDetails($file_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                 WHERE
                    iat_id=iaf_iat_id AND
                    iaf_id=$file_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Removes all attachments (and associated files) related to a set
     * of specific issues.
     *
     * @access  public
     * @param   array $ids The issue IDs that need to be removed
     * @return  boolean Whether the removal worked or not
     */
    function removeByIssues($ids)
    {
        $items = @implode(", ", $ids);
        $stmt = "SELECT
                    iat_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment
                 WHERE
                    iat_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            for ($i = 0; $i < count($res); $i++) {
                Attachment::remove($res[$i]);
            }
            return true;
        }
    }


    /**
     * Method used to remove attachments from the database.
     *
     * @access  public
     * @return  integer Numeric code used to check for any errors
     */
    function remove($iat_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = "SELECT
                    iat_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment
                 WHERE
                    iat_id=$iat_id AND
                    iat_usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (empty($res)) {
                return -2;
            } else {
                $files = Attachment::getFileList($iat_id);
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment
                         WHERE
                            iat_id=$iat_id AND
                            iat_iss_id=$res AND
                            iat_usr_id=$usr_id";
                $GLOBALS["db_api"]->dbh->query($stmt);
                for ($i = 0; $i < count($files); $i++) {
                    Attachment::removeFile($files[$i]['iaf_id']);
                }
                Issue::markAsUpdated($res);
                // need to save a history entry for this
                History::add($res, $usr_id, History::getTypeID('attachment_removed'), 'Attachment removed by ' . User::getFullName($usr_id));
                return 1;
            }
        }
    }


    /**
     * Method used to remove a specific file from an attachment, since every
     * attachment can have several files associated with it.
     *
     * @access  public
     * @param   integer $iaf_id The attachment file ID
     * @return  void
     */
    function removeFile($iaf_id)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                 WHERE
                    iaf_id=" . $iaf_id;
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to get the full listing of files for a specific attachment.
     *
     * @access  public
     * @param   integer $attachment_id The attachment ID
     * @return  array The full list of files
     */
    function getFileList($attachment_id)
    {
        $stmt = "SELECT
                    iaf_id,
                    iaf_filename,
                    iaf_filesize
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                 WHERE
                    iaf_iat_id=$attachment_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["iaf_filesize"] = Misc::formatFileSize($res[$i]["iaf_filesize"]);
            }
            return $res;
        }
    }


    /**
     * Method used to return the full list of attachments related to a specific
     * issue in the database.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The full list of attachments
     */
    function getList($issue_id)
    {
        $stmt = "SELECT
                    iat_id,
                    iat_usr_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description,
                    iat_unknown_user
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iat_iss_id=$issue_id AND
                    iat_usr_id=usr_id
                 ORDER BY
                    iat_created_date DESC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["iat_description"] = Misc::activateIssueLinks(nl2br(htmlspecialchars($res[$i]["iat_description"])));
                $res[$i]["files"] = Attachment::getFileList($res[$i]["iat_id"]);
                $res[$i]["iat_created_date"] = Date_API::getFormattedDate($res[$i]["iat_created_date"]);
                
                // if there is an unknown user, user that instead of the user_full_name
                if (!empty($res[$i]["iat_unknown_user"])) {
                    $res[$i]["usr_full_name"] = $res[$i]["iat_unknown_user"];
                }
            }
            return $res;
        }
    }


    /**
     * Method used to associate an attachment to an issue, and all of its 
     * related files. It also notifies any subscribers of this new attachment.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  integer Numeric code used to check for any errors
     */
    function attach($usr_id)
    {
        global $HTTP_POST_VARS, $HTTP_POST_FILES;

        $files = array();
        for ($i = 0; $i < count($HTTP_POST_FILES["attachment"]["name"]); $i++) {
            $filename = @$HTTP_POST_FILES["attachment"]["name"][$i];
            if (empty($filename)) {
                continue;
            }
            $blob = Misc::getFileContents($HTTP_POST_FILES["attachment"]["tmp_name"][$i]);
            if (empty($blob)) {
                return -1;
            }
            $files[] = array(
                "filename"  =>  $filename,
                "type"      =>  $HTTP_POST_FILES['attachment']['type'][$i],
                "blob"      =>  $blob
            );
        }
        if (count($files) < 1) {
            return -1;
        }
        $attachment_id = Attachment::add($HTTP_POST_VARS["issue_id"], $usr_id, $HTTP_POST_VARS["file_description"]);
        foreach ($files as $file) {
            Attachment::addFile($attachment_id, $HTTP_POST_VARS["issue_id"], $file["filename"], $file["type"], $file["blob"]);
        }
        
        Issue::markAsUpdated($HTTP_POST_VARS["issue_id"]);
        // XXX: check if we need to change the issue status to 'Waiting on Developer'
        /*
        if (User::getRoleByUser($usr_id) == User::getRoleID('Customer')) {
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && (Issue::getStatusID($HTTP_POST_VARS["issue_id"]) != Status::getStatusID('Pending'))) {
                Issue::markAsWaitingOnDeveloper($HTTP_POST_VARS["issue_id"], $status_id, 'file');
                Issue::recordLastCustomerAction($HTTP_POST_VARS["issue_id"]);
            }
        }
        */
        // need to save a history entry for this
        History::add($HTTP_POST_VARS["issue_id"], $usr_id, History::getTypeID('attachment_added'), 'Attachment uploaded by ' . User::getFullName($usr_id));
        // send notifications for the issue being updated
        Notification::notify($HTTP_POST_VARS["issue_id"], 'files', $attachment_id);
        return 1;
    }


    /**
     * Method used to add files to a specific attachment in the database.
     *
     * @access  public
     * @param   integer $attachment_id The attachment ID
     * @param   integer $issue_id The issue ID
     * @param   string $filename The filename to be added
     * @return  boolean
     */
    function addFile($attachment_id, $issue_id, $filename, $filetype, $blob)
    {
        $filesize = strlen($blob);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment_file
                 (
                    iaf_iat_id,
                    iaf_filename,
                    iaf_filesize,
                    iaf_filetype,
                    iaf_file
                 ) VALUES (
                    $attachment_id,
                    '" . Misc::escapeString($filename) . "',
                    '" . $filesize . "',
                    '" . Misc::escapeString($filetype) . "',
                    '" . addslashes($blob) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to add an attachment to the database.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   string $description The description for this new attachment
     * @param   string $unknown_user The email of the user who originally sent this email, who doesn't have an account.
     * @return  integer The new attachment ID
     */
    function add($issue_id, $usr_id, $description, $unknown_user = FALSE)
    {
        global $HTTP_POST_VARS;

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment
                 (
                    iat_iss_id,
                    iat_usr_id,
                    iat_created_date,
                    iat_description";
        if ($unknown_user != false) {
            $stmt .= ", iat_unknown_user ";
        }
        $stmt .=") VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($description) . "'";
        if ($unknown_user != false) {
            $stmt .= ", '$unknown_user'";
        }
        $stmt .= ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return $GLOBALS["db_api"]->get_last_insert_id();
        }
    }


    /**
     * Method used to replace unsafe characters by safe characters.
     *
     * @access  public
     * @param   string $name The name of the file to be checked
     * @param   integer $maxlen The maximum length of the filename
     * @return  string The 'safe' version of the filename
     */
    function nameToSafe($name, $maxlen=250)
    {
        $noalpha = 'áéíóúàèìòùäëïöüÁÉÍÓÚÀÈÌÒÙÄËÏÖÜâêîôûÂÊÎÔÛñçÇ@';
        $alpha = 'aeiouaeiouaeiouAEIOUAEIOUAEIOUaeiouAEIOUncCa';
        $name = substr($name, 0, $maxlen);
        $name = strtr($name, $noalpha, $alpha);
        // not permitted chars are replaced with "_"
        return ereg_replace('[^a-zA-Z0-9,._\+\()\-]', '_', $name);
    }


    /**
     * Returns the current maximum file upload size.
     *
     * @access  public
     * @return  string A string containing the formatted max file size.
     */
    function getMaxAttachmentSize()
    {
        $size = ini_get('upload_max_filesize');
        // check if this directive uses the string version (i.e. 256M)
        if (strstr($size, 'M')) {
            $size = str_replace('M', '', $size);
            return Misc::formatFileSize($size * 1024 * 1024);
        } else {
            return Misc::formatFileSize($size);
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Attachment Class');
}
?>