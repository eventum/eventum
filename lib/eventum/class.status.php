<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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


/**
 * Class to handle all business logic related to the way statuses
 * are represented in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Status
{
    /**
     * Returns the label and date field associated with the customization of
     * the given project and status IDs.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $sta_ids The list of status IDs
     * @return  array The label and date field
     */
    function getProjectStatusCustomization($prj_id, $sta_ids)
    {
        $sta_ids = array_unique($sta_ids);
        $stmt = "SELECT
                    psd_sta_id,
                    psd_label,
                    psd_date_field
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date
                 WHERE
                    psd_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    psd_sta_id IN (" . implode(', ', Misc::escapeInteger($sta_ids)) . ")";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Returns the details of a given project status customization entry.
     *
     * @access  public
     * @param   integer $psd_id The customization entry ID
     * @return  array The details
     */
    function getCustomizationDetails($psd_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date
                 WHERE
                    psd_id=" . Misc::escapeInteger($psd_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Removes a given set of customizations.
     *
     * @access  public
     * @param   array $items The customization entry IDs
     * @return  boolean
     */
    function removeCustomization($items)
    {
        $items = @implode(", ", Misc::escapeInteger($items));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date
                 WHERE
                    psd_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the details of a customization entry in the system.
     *
     * @access  public
     * @param   integer $psd_id The customization entry ID
     * @param   integer $prj_id The project ID
     * @param   integer $sta_id The status ID
     * @param   string $date_field The date field name
     * @param   string $label The label that should appear in the issue listing screen
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function updateCustomization($psd_id, $prj_id, $sta_id, $date_field, $label)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date
                 SET
                    psd_prj_id=" . Misc::escapeInteger($prj_id) . ",
                    psd_sta_id=" . Misc::escapeInteger($sta_id) . ",
                    psd_date_field='" . Misc::escapeString($date_field) . "',
                    psd_label='" . Misc::escapeString($label) . "'
                 WHERE
                    psd_id=" . Misc::escapeInteger($psd_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to add a new customization entry to the system.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $sta_id The status ID
     * @param   string $date_field The date field name
     * @param   string $label The label that should appear in the issue listing screen
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function insertCustomization($prj_id, $sta_id, $date_field, $label)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date
                 (
                    psd_prj_id,
                    psd_sta_id,
                    psd_date_field,
                    psd_label
                 ) VALUES (
                    " . Misc::escapeInteger($prj_id) . ",
                    " . Misc::escapeInteger($sta_id) . ",
                    '" . Misc::escapeString($date_field) . "',
                    '" . Misc::escapeString($label) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get a list of all existing customizations.
     *
     * @access  public
     * @return  array The list of available customizations
     */
    function getCustomizationList()
    {
        $stmt = "SELECT
                    psd_id,
                    psd_prj_id,
                    psd_sta_id,
                    psd_label,
                    psd_date_field,
                    prj_title,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status_date,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    prj_id=psd_prj_id AND
                    sta_id=psd_sta_id
                 ORDER BY
                    prj_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $date_fields = Issue::getDateFieldsAssocList(TRUE);
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['date_field'] = $date_fields[$res[$i]['psd_date_field']];
            }
            return $res;
        }
    }


    /**
     * Method used to check whether the given status has a closed context or
     * not.
     *
     * @access  public
     * @return  boolean
     */
    function hasClosedContext($sta_id)
    {
        $stmt = "SELECT
                    sta_is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=" . Misc::escapeInteger($sta_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if (empty($res)) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to add a new custom status to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 (
                    sta_title,
                    sta_abbreviation,
                    sta_rank,
                    sta_color,
                    sta_is_closed
                 ) VALUES (
                    '" . Misc::escapeString($_POST['title']) . "',
                    '" . Misc::escapeString($_POST['abbreviation']) . "',
                    " . Misc::escapeInteger($_POST['rank']) . ",
                    '" . Misc::escapeString($_POST['color']) . "',
                    " . Misc::escapeInteger($_POST['is_closed']) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_status_id = DB_Helper::get_last_insert_id();
            // now populate the project-status mapping table
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($new_status_id, $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to update the details of a given custom status.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function update()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 SET
                    sta_title='" . Misc::escapeString($_POST["title"]) . "',
                    sta_abbreviation='" . Misc::escapeString($_POST["abbreviation"]) . "',
                    sta_rank=" . Misc::escapeInteger($_POST['rank']) . ",
                    sta_color='" . Misc::escapeString($_POST["color"]) . "',
                    sta_is_closed=" . Misc::escapeInteger($_POST['is_closed']) . "
                 WHERE
                    sta_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $projects = self::getAssociatedProjects($_POST['id']);
            $current_projects = array_keys($projects);
            // remove all of the associations with projects, then add them all again
            self::removeProjectAssociations($_POST['id']);
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($_POST['id'], $prj_id);
            }
            // need to update all issues that are not supposed to have the changed sta_id to '0'
            $removed_projects = array();
            foreach ($current_projects as $project_id) {
                if (!in_array($project_id, $_POST['projects'])) {
                    $removed_projects[] = $project_id;
                }
            }
            if (count($removed_projects) > 0) {
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                         SET
                            iss_sta_id=0
                         WHERE
                            iss_sta_id=" . Misc::escapeInteger($_POST['id']) . " AND
                            iss_prj_id IN (" . implode(', ', $removed_projects) . ")";
                $res = DB_Helper::getInstance()->query($stmt);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to remove a set of custom statuses.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeProjectAssociations($_POST['items']);
            // also set all issues currently set to these statuses to status '0'
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     SET
                        iss_sta_id=0
                     WHERE
                        iss_sta_id IN ($items)";
            DB_Helper::getInstance()->query($stmt);
            return true;
        }
    }


    /**
     * Method used to add a project association to a status.
     *
     * @access  public
     * @param   integer $sta_id The status ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    function addProjectAssociation($sta_id, $prj_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 (
                    prs_sta_id,
                    prs_prj_id
                 ) VALUES (
                    " . Misc::escapeInteger($sta_id) . ",
                    " . Misc::escapeInteger($prj_id) . "
                 )";
        DB_Helper::getInstance()->query($stmt);
    }


    /**
     * Method used to remove the project associations for a given
     * custom status.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function removeProjectAssociations($sta_id, $prj_id=FALSE)
    {
        if (!is_array($sta_id)) {
            $sta_id = array($sta_id);
        }
        $items = @implode(", ", Misc::escapeInteger($sta_id));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_sta_id IN ($items)";
        if ($prj_id) {
            $stmt .= " AND prs_prj_id=" . Misc::escapeInteger($prj_id);
        }
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the details of a given status ID.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @return  array The status details
     */
    function getDetails($sta_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=" . Misc::escapeInteger($sta_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(self::getAssociatedProjects($res['sta_id']));
            return $res;
        }
    }


    /**
     * Method used to get the list of statuses ordered by title.
     *
     * @access  public
     * @return  array The list of statuses
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_rank ASC,
                    sta_title";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated projects
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['projects'] = implode(", ", array_values(self::getAssociatedProjects($res[$i]['sta_id'])));
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of associated projects for a given
     * custom status.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @return  array The list of projects
     */
    function getAssociatedProjects($sta_id)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prj_id=prs_prj_id AND
                    prs_sta_id=" . Misc::escapeInteger($sta_id);
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the status ID for a given status title.
     *
     * @access  public
     * @param   string $sta_title The status title
     * @return  integer The status ID
     */
    public static function getStatusID($sta_title)
    {
        static $returns;

        if (!empty($returns[$sta_title])) {
            return $returns[$sta_title];
        }

        $stmt = "SELECT
                    sta_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_title='" . Misc::escapeString($sta_title) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$sta_title] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the status title for a given status ID.
     *
     * @access  public
     * @param   integer $sta_id The status ID
     * @return  string The status title
     */
    function getStatusTitle($sta_id)
    {
        $stmt = "SELECT
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=" . Misc::escapeInteger($sta_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available closed-context statuses as an
     * associative array in the style of (abbreviation => title)
     *
     * @access  public
     * @param   array $prj_id List of project IDs
     * @return  array The list of closed-context statuses
     */
    function getClosedAbbreviationAssocList($prj_id)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }
        $items = @implode(", ", Misc::escapeInteger($prj_id));
        $stmt = "SELECT
                    UPPER(sta_abbreviation),
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id IN ($items) AND
                    prs_sta_id=sta_id AND
                    sta_is_closed=1
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (abbreviation => title)
     *
     * @access  public
     * @param   array $prj_id List of project IDs
     * @param   boolean $show_closed Whether to also return closed-context statuses or not
     * @return  array The list of statuses
     */
    function getAbbreviationAssocList($prj_id, $show_closed)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }
        $items = @implode(", ", Misc::escapeInteger($prj_id));
        $stmt = "SELECT
                    UPPER(sta_abbreviation),
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id IN ($items) AND
                    prs_sta_id=sta_id";
        if (!$show_closed) {
            $stmt .= " AND sta_is_closed=0 ";
        }
        $stmt .= "
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @access  public
     * @param   array $prj_id List of project IDs
     * @param   boolean $show_closed Whether to show closed context statuses or not
     * @return  array The list of statuses
     */
    function getAssocStatusList($prj_id, $show_closed = TRUE)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }
        $items = @implode(", ", Misc::escapeInteger($prj_id));
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id IN ($items) AND
                    prs_sta_id=sta_id";
        if (!$show_closed) {
            $stmt .= " AND sta_is_closed=0 ";
        }
        $stmt .= "
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @access  public
     * @return  array The list of statuses
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title). Only return the list of statuses that have
     * a 'closed' context.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of statuses
     */
    function getClosedAssocList($prj_id)
    {
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    prs_sta_id=sta_id AND
                    sta_is_closed=1
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of statuses and their respective colors
     *
     * @access  public
     * @return  array List of statuses
     */
    function getStatusColors()
    {
        $stmt = "SELECT
                    sta_color,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }
}
