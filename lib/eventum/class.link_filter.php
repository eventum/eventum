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

use Eventum\Attachment\AttachmentManager;
use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;
use Eventum\LinkFilter\IssueLinkFilter;
use Eventum\LinkFilter\LinkFilter;
use Eventum\Markdown\MarkdownRendererInterface;
use Eventum\ServiceContainer;

/**
 * Class to handle parsing content for links.
 */
class Link_Filter
{
    /**
     * Returns information about a specific link filter.
     *
     * @param   int $lfi_id the ID of the link filter to return info about
     * @return  array an array of information
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
                    `link_filter`
                WHERE
                    lfi_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($sql, [$lfi_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (count($res) > 0) {
            $sql = 'SELECT
                        plf_prj_id
                    FROM
                        `project_link_filter`
                    WHERE
                        plf_lfi_id = ?';
            try {
                $projects = DB_Helper::getInstance()->getColumn($sql, [$res['lfi_id']]);
            } catch (DatabaseException $e) {
                $projects = [];
            }

            if ($projects === null) {
                $projects = [];
            }
            $res['projects'] = $projects;
        }

        return $res;
    }

    /**
     * Lists the link filters currently in the system.
     *
     * @return array an array of information
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
                    `link_filter`
                ORDER BY
                    lfi_id';
        try {
            $res = DB_Helper::getInstance()->getAll($sql);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$row) {
            $sql = 'SELECT
                        plf_prj_id,
                        prj_title
                    FROM
                        `project_link_filter`,
                        `project`
                    WHERE
                        prj_id = plf_prj_id AND
                        plf_lfi_id = ?';
            try {
                $projects = DB_Helper::getInstance()->getPair($sql, [$row['lfi_id']]);
            } catch (DatabaseException $e) {
                $projects = [];
            }
            if ($projects === null) {
                $projects = [];
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
     * @return int 1 if insert was successful, -1 otherwise
     */
    public static function insert()
    {
        $sql = 'INSERT INTO
                    `link_filter`
                (
                    lfi_pattern,
                    lfi_replacement,
                    lfi_usr_role,
                    lfi_description
                ) VALUES (
                    ?, ?, ?, ?
                )';
        $params = [$_REQUEST['pattern'], $_REQUEST['replacement'], $_REQUEST['usr_role'], $_REQUEST['description']];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $lfi_id = DB_Helper::get_last_insert_id();
        foreach ($_REQUEST['projects'] as $prj_id) {
            $sql = 'INSERT INTO
                        `project_link_filter`
                    (
                        plf_prj_id,
                        plf_lfi_id
                    ) VALUES (
                        ?, ?
                    )';
            try {
                DB_Helper::getInstance()->query($sql, [$prj_id, $lfi_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Removes link filters from the database
     *
     * @return int 1 if delete was successful, -1 otherwise
     */
    public static function remove()
    {
        $items = $_REQUEST['items'];
        $itemlist = DB_Helper::buildList($items);

        $sql = "DELETE FROM
                    `link_filter`
                WHERE
                    lfi_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($sql, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        $sql = "DELETE FROM
                    `project_link_filter`
                WHERE
                    plf_lfi_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($sql, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Updates link filter information.
     *
     * @return int 1 if insert was successful, -1 otherwise
     */
    public static function update()
    {
        $sql = 'UPDATE
                    `link_filter`
                SET
                    lfi_pattern = ?,
                    lfi_replacement = ?,
                    lfi_usr_role = ?,
                    lfi_description = ?
                WHERE
                    lfi_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [
                $_REQUEST['pattern'],
                $_REQUEST['replacement'],
                $_REQUEST['usr_role'],
                $_REQUEST['description'],
                $_REQUEST['id'],
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $sql = 'DELETE FROM
                    `project_link_filter`
                WHERE
                    plf_lfi_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$_REQUEST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($_REQUEST['projects'] as $prj_id) {
            $sql = 'INSERT INTO
                        `project_link_filter`
                    (
                        plf_prj_id,
                        plf_lfi_id
                    ) VALUES (
                        ?, ?
                    )';
            try {
                DB_Helper::getInstance()->query($sql, [$prj_id, $_REQUEST['id']]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    public static function markdownFormat(string $text, bool $inline = false): string
    {
        if ($inline) {
            $renderer = ServiceContainer::get(MarkdownRendererInterface::RENDER_INLINE);
        } else {
            $renderer = ServiceContainer::get(MarkdownRendererInterface::RENDER_BLOCK);
        }

        return $renderer->render($text);
    }

    /**
     * Processes text through all link filters.
     *
     * @param   int $prj_id The ID of the project
     * @param   string $text The text to process
     * @param bool $inline
     * @return  string the processed text
     */
    public static function processText($prj_id, $text, bool $inline = false): string
    {
        $linkFilter = self::getLinkFilter($prj_id);
        $text = $linkFilter->replace($text);
        $text = self::markdownFormat($text, $inline);

        return $text;
    }

    /**
     * Callback function to be used from template class.
     *
     * @param   string $text The text to process
     * @return  string the processed text
     */
    public static function activateLinks($text)
    {
        return self::processText(Auth::getCurrentProject(), $text);
    }

    /**
     * @param string $text
     * @param int $issue_id
     * @return string
     */
    public static function textFormat($text, $issue_id): string
    {
        $text = self::activateLinks($text);
        $text = self::activateAttachmentLinks($text, $issue_id);

        return $text;
    }

    /**
     * Callback function to be used from template class.
     *
     * @param   string $text The text to process
     * @param   int $issue_id The ID of the issue from where attachment list is taken
     * @return  string the processed text
     */
    public static function activateAttachmentLinks($text, $issue_id)
    {
        // build list of files to replace, so duplicate matches will always
        // take last matching filename.
        $files = [];
        foreach (AttachmentManager::getList($issue_id) as $attachment) {
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
            $text = preg_replace('/attachment:?\s*' . preg_quote($file, '/') . '\b/', $link, $text);
        }

        return $text;
    }

    /**
     * Returns an array of patterns and replacements.
     */
    public static function getLinkFilter(?int $prj_id = null): LinkFilter
    {
        static $cache;

        $initialize = static function (?int $prj_id) {
            $linkFilter = new LinkFilter(ServiceContainer::getRequest());
            $linkFilter->addFilter(new IssueLinkFilter(Setup::getBaseUrl()));

            if ($prj_id) {
                $linkFilter->addRules(self::getFiltersByProject($prj_id));
                Workflow::addLinkFilters($linkFilter, $prj_id);
            }

            return $linkFilter;
        };

        return $cache[(int)$prj_id] ?? $cache[(int)$prj_id] = $initialize($prj_id);
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @param   int $prj_id The ID of the project
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
                    `link_filter`,
                    `project_link_filter`
                WHERE
                    lfi_id = plf_lfi_id AND
                    lfi_usr_role < ? AND
                    plf_prj_id = ?
                ORDER BY
                    lfi_id";
        $params = [Auth::getCurrentRole(), $prj_id];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params, AdapterInterface::DB_FETCHMODE_DEFAULT);
        } catch (DatabaseException $e) {
            return [];
        }

        $filters[$prj_id] = $res;

        return $res;
    }

    /**
     * Whether markdown renderer enabled.
     * Can be enabled from setup/preferences as experiment.
     *
     * @param bool $value force value. internal for testing
     * @return bool
     * @deprecated since 3.8.0 this returns always true
     */
    public static function markdownEnabled($value = null): bool
    {
        return true;
    }
}
