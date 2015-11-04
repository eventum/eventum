<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Class to handle parsing content for links.
 */
class Link_Filter
{
    /**
     * Returns information about a specific link filter.
     *
     * @param   integer $lfi_id The ID of the link filter to return info about.
     * @return  array An array of information.
     */
    public static function getDetails($lfi_id)
    {
        $sql = 'SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    {{%link_filter}}
                WHERE
                    lfi_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($sql, array($lfi_id));
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $sql = 'SELECT
                        plf_prj_id
                    FROM
                        {{%project_link_filter}}
                    WHERE
                        plf_lfi_id = ?';
            try {
                $projects = DB_Helper::getInstance()->getColumn($sql, array($res['lfi_id']));
            } catch (DbException $e) {
                $projects = array();
            }

            if ($projects === null) {
                $projects = array();
            }
            $res['projects'] = $projects;
        }

        return $res;
    }

    /**
     * Lists the link filters currently in the system.
     *
     * @return array An array of information.
     */
    public static function getList()
    {
        $sql = 'SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    {{%link_filter}}
                ORDER BY
                    lfi_id';
        try {
            $res = DB_Helper::getInstance()->getAll($sql);
        } catch (DbException $e) {
            return array();
        }

        foreach ($res as &$row) {
            $sql = 'SELECT
                        plf_prj_id,
                        prj_title
                    FROM
                        {{%project_link_filter}},
                        {{%project}}
                    WHERE
                        prj_id = plf_prj_id AND
                        plf_lfi_id = ?';
            try {
                $projects = DB_Helper::getInstance()->getPair($sql, array($row['lfi_id']));
            } catch (DbException $e) {
                $projects = array();
            }
            if ($projects === null) {
                $projects = array();
            }
            $row['projects'] = array_keys($projects);
            $row['project_names'] = array_values($projects);
            $row['min_usr_role_name'] = User::getRole($row['lfi_usr_role']);
        }

        return $res;
    }

    /**
     * Inserts a new link filter into the database.
     *
     * @return integer 1 if insert was successful, -1 otherwise
     */
    public static function insert()
    {
        $sql = 'INSERT INTO
                    {{%link_filter}}
                (
                    lfi_pattern,
                    lfi_replacement,
                    lfi_usr_role,
                    lfi_description
                ) VALUES (
                    ?, ?, ?, ?
                )';
        $params = array($_REQUEST['pattern'], $_REQUEST['replacement'], $_REQUEST['usr_role'], $_REQUEST['description']);
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DbException $e) {
            return -1;
        }

        $lfi_id = DB_Helper::get_last_insert_id();
        foreach ($_REQUEST['projects'] as $prj_id) {
            $sql = 'INSERT INTO
                        {{%project_link_filter}}
                    (
                        plf_prj_id,
                        plf_lfi_id
                    ) VALUES (
                        ?, ?
                    )';
            try {
                DB_Helper::getInstance()->query($sql, array($prj_id, $lfi_id));
            } catch (DbException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Removes link filters from the database
     *
     * @return integer 1 if delete was successful, -1 otherwise.
     */
    public static function remove()
    {
        $items = $_REQUEST['items'];
        $itemlist = DB_Helper::buildList($items);

        $sql = "DELETE FROM
                    {{%link_filter}}
                WHERE
                    lfi_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($sql, $items);
        } catch (DbException $e) {
            return -1;
        }

        $sql = "DELETE FROM
                    {{%project_link_filter}}
                WHERE
                    plf_lfi_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($sql, $items);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Updates link filter information.
     *
     * @return integer 1 if insert was successful, -1 otherwise
     */
    public static function update()
    {
        $sql = 'UPDATE
                    {{%link_filter}}
                SET
                    lfi_pattern = ?,
                    lfi_replacement = ?,
                    lfi_usr_role = ?,
                    lfi_description = ?
                WHERE
                    lfi_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, array(
                $_REQUEST['pattern'],
                $_REQUEST['replacement'],
                $_REQUEST['usr_role'],
                $_REQUEST['description'],
                $_REQUEST['id'],
            ));
        } catch (DbException $e) {
            return -1;
        }

        $sql = 'DELETE FROM
                    {{%project_link_filter}}
                WHERE
                    plf_lfi_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, array($_REQUEST['id']));
        } catch (DbException $e) {
            return -1;
        }

        foreach ($_REQUEST['projects'] as $prj_id) {
            $sql = 'INSERT INTO
                        {{%project_link_filter}}
                    (
                        plf_prj_id,
                        plf_lfi_id
                    ) VALUES (
                        ?, ?
                    )';
            try {
                DB_Helper::getInstance()->query($sql, array($prj_id, $_REQUEST['id']));
            } catch (DbException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Processes text through all link filters.
     *
     * @param   integer $prj_id The ID of the project
     * @param   string $text The text to process
     * @param   string $class The CSS class to use on the actual links
     * @return  string The processed text.
     */
    public static function processText($prj_id, $text, $class = 'link')
    {

        // process issue link seperatly since it has to do something special
        $text = Misc::activateLinks($text, $class);

        $filters = array_merge(self::getFilters(), self::getFiltersByProject($prj_id), Workflow::getLinkFilters($prj_id));
        foreach ((array) $filters as $filter) {
            list($pattern, $replacement) = $filter;
            // replacement may be a callback, provided by workflow
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
     * @param   string $text The text to process
     * @return  string the processed text.
     */
    public static function activateLinks($text)
    {
        return self::processText(Auth::getCurrentProject(), $text);
    }

    /**
     * Callback function to be used from template class.
     *
     * @param   string $text The text to process
     * @param   integer $issue_id The ID of the issue from where attachment list is taken
     * @return  string the processed text.
     */
    public function activateAttachmentLinks($text, $issue_id)
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
            $text = preg_replace('/attachment:?\s*'.preg_quote($file, '/').'\b/', $link, $text);
        }

        return $text;
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @return  array An array of patterns and replacements
     */
    public static function getFilters()
    {
        // link eventum issue ids
        $base_url = APP_BASE_URL;
        $patterns = array(
            array('/(?P<match>issue:?\s\#?(?P<issue_id>\d+))/i', array(__CLASS__, 'LinkFilter_issues')),
            # lookbehind here avoid matching "open http:// in new window" and href="http://"
            array("#(?<!open |href=\"){$base_url}view\.php\?id=(?P<issue_id>\d+)#", array(__CLASS__, 'LinkFilter_issues')),
        );

        return $patterns;
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @param   integer $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    private static function getFiltersByProject($prj_id)
    {
        static $filters;

        // poor man's caching system
        if (!empty($filters[$prj_id])) {
            return $filters[$prj_id];
        }

        $stmt = "SELECT
                    CONCAT('/', lfi_pattern, '/i'),
                    lfi_replacement
                FROM
                    {{%link_filter}},
                    {{%project_link_filter}}
                WHERE
                    lfi_id = plf_lfi_id AND
                    lfi_usr_role < ? AND
                    plf_prj_id = ?
                ORDER BY
                    lfi_id";
        $params = array(Auth::getCurrentRole(), $prj_id);
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params, DbInterface::DB_FETCHMODE_DEFAULT);
        } catch (DbException $e) {
            return array();
        }

        $filters[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    public static function LinkFilter_issues($matches)
    {
        $issue_id = $matches['issue_id'];
        // check if the issue is still open
        if (Issue::isClosed($issue_id)) {
            $class = 'closed';
        } else {
            $class = '';
        }
        $issue_title = Issue::getTitle($issue_id);
        $link_title = htmlspecialchars("issue {$issue_id} - {$issue_title}");

        // use named capture 'match' if present
        $match = isset($matches['match']) ? $matches['match'] : "issue {$issue_id}";

        return "<a title=\"{$link_title}\" class=\"{$class}\" href=\"view.php?id={$matches['issue_id']}\">{$match}</a>";
    }
}
