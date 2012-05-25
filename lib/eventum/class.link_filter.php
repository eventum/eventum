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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

/**
 * Class to handle parsing content for links.
 *
 * @author  Bryan Alsdorf <bryan@mysql.com>
 * @version 1.0
 */
class Link_Filter
{
    /**
     * Returns information about a specific link filter.
     *
     * @access  public
     * @param   integer $lfi_id The ID of the link filter to return info about.
     * @return  array An array of information.
     */
    function getDetails($lfi_id)
    {
        $sql = "SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                WHERE
                    lfi_id = " . Misc::escapeInteger($lfi_id);
        $res = DB_Helper::getInstance()->getRow($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } elseif (count($res) > 0) {
            $sql = "SELECT
                        plf_prj_id
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                    WHERE
                        plf_lfi_id = " . $res['lfi_id'];
            $projects = DB_Helper::getInstance()->getCol($sql);
            if (PEAR::isError($projects)) {
                Error_Handler::logError(array($projects->getMessage(), $projects->getDebugInfo()), __FILE__, __LINE__);
                $projects = array();
            } elseif ($projects === null) {
                $projects = array();
            }
            $res["projects"] = $projects;
        }
        return $res;
    }


    /**
     * Lists the link filters currently in the system.
     *
     * @return array An array of information.
     */
    function getList()
    {
        $sql = "SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                ORDER BY
                    lfi_id";
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        for ($i = 0; $i < count($res); $i++) {
            $sql = "SELECT
                        plf_prj_id,
                        prj_title
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                    WHERE
                        prj_id = plf_prj_id AND
                        plf_lfi_id = " . $res[$i]['lfi_id'];
            $projects = DB_Helper::getInstance()->getAssoc($sql);
            if (PEAR::isError($projects)) {
                Error_Handler::logError(array($projects->getMessage(), $projects->getDebugInfo()), __FILE__, __LINE__);
                $projects = array();
            } elseif ($projects === null) {
                $projects = array();
            }
            $res[$i]["projects"] = array_keys($projects);
            $res[$i]["project_names"] = array_values($projects);
            $res[$i]["min_usr_role_name"] = User::getRole($res[$i]["lfi_usr_role"]);
        }
        return $res;
    }


    /**
     * Inserts a new link filter into the database.
     *
     * @return integer 1 if insert was successful, -1 otherwise
     */
    function insert()
    {
        $sql = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                (
                    lfi_pattern,
                    lfi_replacement,
                    lfi_usr_role,
                    lfi_description
                ) VALUES (
                    '" . Misc::escapeString($_REQUEST["pattern"]) . "',
                    '" . Misc::escapeString($_REQUEST["replacement"]) . "',
                    '" . Misc::escapeInteger($_REQUEST["usr_role"]) . "',
                    '" . Misc::escapeString($_REQUEST["description"]) . "'
                )";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $lfi_id = DB_Helper::get_last_insert_id();
            foreach ($_REQUEST["projects"] as $prj_id) {
                $sql = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                        (
                            plf_prj_id,
                            plf_lfi_id
                        ) VALUES (
                            $prj_id,
                            $lfi_id
                        )";
                $res = DB_Helper::getInstance()->query($sql);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
            }
            return 1;
        }
    }


    /**
     * Removes link filters from the database
     *
     * @return integer 1 if delete was successful, -1 otherwise.
     */
    function remove()
    {
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                WHERE
                    lfi_id IN(" . join(',', Misc::escapeInteger($_REQUEST["items"])) . ")";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                WHERE
                    plf_lfi_id IN(" . join(',', Misc::escapeInteger($_REQUEST["items"])) . ")";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Updates link filter information.
     *
     * @return integer 1 if insert was successful, -1 otherwise
     */
    function update()
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                SET
                    lfi_pattern = '" . Misc::escapeString($_REQUEST["pattern"]) . "',
                    lfi_replacement = '" . Misc::escapeString($_REQUEST["replacement"]) . "',
                    lfi_usr_role = '" . Misc::escapeInteger($_REQUEST["usr_role"]) . "',
                    lfi_description = '" . Misc::escapeString($_REQUEST["description"]) . "'
                WHERE
                    lfi_id = " . $_REQUEST["id"];
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $sql = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                    WHERE
                        plf_lfi_id = " . Misc::escapeInteger($_REQUEST["id"]);
            $res = DB_Helper::getInstance()->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
            foreach (Misc::escapeInteger($_REQUEST["projects"]) as $prj_id) {
                $sql = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                        (
                            plf_prj_id,
                            plf_lfi_id
                        ) VALUES (
                            $prj_id,
                            " . Misc::escapeInteger($_REQUEST["id"]) . "
                        )";
                $res = DB_Helper::getInstance()->query($sql);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
            }
            return 1;
        }
    }


    /**
     * Processes text through all link filters.
     *
     * @access  public
     * @param   integer $prj_id The ID of the project
     * @param   string $text The text to process
     * @param   string $class The CSS class to use on the actual links
     * @return  string The processed text.
     */
    function processText($prj_id, $text, $class = "link")
    {

        // process issue link seperatly since it has to do something special
        $text = Misc::activateLinks($text, $class);

        $filters = array_merge(self::getFilters(), self::getFiltersByProject($prj_id), Workflow::getLinkFilters($prj_id));
        foreach ((array )$filters as $filter) {
            list($pattern, $replacement) = $filter;
            // if replacement may be a callback, provided by workflow
            if (is_callable($replacement)) {
                $text = preg_replace_callback($pattern, $replacement, $text);
            } else {
                $text = preg_replace($pattern, $replacement, $text);
            }
        }

        return $text;
    }


    /**
     * Callback function to be used from template class.
     *
     * @access  public
     * @param   string $text The text to process
     * @return  string the processed text.
     */
    function activateLinks($text)
    {
        return self::processText(Auth::getCurrentProject(), $text);
    }

    /**
     * Callback function to be used from template class.
     *
     * @access  public
     * @param   string $text The text to process
     * @param   integer $issue_id The ID of the issue from where attachment list is taken
     * @return  string the processed text.
     */
    function activateAttachmentLinks($text, $issue_id)
    {
        // build list of files to replace, so duplicate matches will always
        // take last matching filename.
        $files = array();
        foreach (Attachment::getList($issue_id) as $attachment) {
            foreach ($attachment['files'] as $file) {
                // TRANSLATORS: %1: iaf_filename, %2: iaf_filesize
                $title = ev_gettext('download file (%1$s - %2$s)', $file['iaf_filename'], $file['iaf_filesize']);
                $link = sprintf('<a class="link" target="_blank" title="%s" href="download.php?cat=attachment&id=%d">%s</a>',
                    htmlspecialchars($title), htmlspecialchars($file['iaf_id']),
                    htmlspecialchars($file['iaf_filename'])
                );
                $files[$file['iaf_filename']] = $link;
            }
        }

        foreach ($files as $file => $link) {
            // we use attachment prefix, so we don't accidentally match already processed urls
            $text = preg_replace("/attachment:?\s*\Q$file\E\b/", $link, $text);
        }
        return $text;
    }


    /**
     * Returns an array of patterns and replacements.
     *
     * @access  private
     * @return  array An array of patterns and replacements
     */
    private static function getFilters()
    {
        // link eventum issue ids
        $patterns = array(
            array('/issue:?\s\#?(?P<issue_id>\d+)/i', array(__CLASS__, 'LinkFilter_issues')),
        );
        return $patterns;
    }


    /**
     * Returns an array of patterns and replacements.
     *
     * @access  private
     * @param   integer $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    private static function getFiltersByProject($prj_id)
    {
        static $filters;

        $prj_id = Misc::escapeInteger($prj_id);

        // poor man's caching system
        if (!empty($filters[$prj_id])) {
            return $filters[$prj_id];
        }

        $stmt = "SELECT
                    CONCAT('/', lfi_pattern, '/i'),
                    lfi_replacement
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                WHERE
                    lfi_id = plf_lfi_id AND
                    lfi_usr_role < " . Auth::getCurrentRole() . " AND
                    plf_prj_id = $prj_id
                ORDER BY
                    lfi_id";
        $res = DB_Helper::getInstance()->getAll($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }

        $filters[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @access  public
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    private static function LinkFilter_issues($matches)
    {
        // check if the issue is still open
        if (Issue::isClosed($matches['issue_id'])) {
            $class = 'closed_link';
        } else {
            $class = 'link';
        }
        $issue_title = Issue::getTitle($matches['issue_id']);
        $link_title = htmlspecialchars("issue {$matches['issue_id']} - {$issue_title}");
        return "<a title=\"{$link_title}\" class=\"{$class}\" href=\"view.php?id={$matches['issue_id']}\">{$matches[0]}</a>";
    }
}
