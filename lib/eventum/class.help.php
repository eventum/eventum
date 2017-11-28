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

/**
 * Class to handle the business logic related to the help
 * documentation, such as providing a dynamic list of topics related
 * to the current topic and such.
 */
class Help
{
    private static $topics;

    private static function getTopics()
    {
        if (self::$topics !== null) {
            return self::$topics;
        }

        // we need this in function as function calls are not allowed in static properties
        self::$topics = [
            'main' => [
                'title' => ev_gettext('Help Topics'),
                'parent' => '',
            ],
            'report' => [
                'title' => ev_gettext('Reporting Issues'),
                'parent' => 'main',
            ],
            'report_category' => [
                'title' => ev_gettext('Category Field'),
                'parent' => 'report',
            ],
            'report_priority' => [
                'title' => ev_gettext('Priority Field'),
                'parent' => 'report',
            ],
            'report_assignment' => [
                'title' => ev_gettext('Assignment Field'),
                'parent' => 'report',
            ],
            'report_release' => [
                'title' => ev_gettext('Scheduled Release Field'),
                'parent' => 'report',
            ],
            'report_summary' => [
                'title' => ev_gettext('Summary Field'),
                'parent' => 'report',
            ],
            'report_description' => [
                'title' => ev_gettext('Description Field'),
                'parent' => 'report',
            ],
            'report_estimated_dev_time' => [
                'title' => ev_gettext('Estimated Development Time Field'),
                'parent' => 'report',
            ],
            'scm_integration' => [
                'title' => ev_gettext('SCM Integration'),
                'parent' => 'main',
            ],
            'scm_integration_usage' => [
                'title' => ev_gettext('Usage Examples'),
                'parent' => 'scm_integration',
            ],
            'scm_integration_installation' => [
                'title' => ev_gettext('Installation Instructions'),
                'parent' => 'scm_integration',
            ],
            'list' => [
                'title' => ev_gettext('Listing / Searching for Issues'),
                'parent' => 'main',
            ],
            'adv_search' => [
                'title' => ev_gettext('Advanced Search / Creating Custom Queries'),
                'parent' => 'main',
            ],
            'support_emails' => [
                'title' => ev_gettext('Associate Emails'),
                'parent' => 'main',
            ],
            'preferences' => [
                'title' => ev_gettext('Account Preferences'),
                'parent' => 'main',
            ],
            'notifications' => [
                'title' => ev_gettext('Email Notifications'),
                'parent' => 'main',
            ],
            'view' => [
                'title' => ev_gettext('Viewing Issues'),
                'parent' => 'main',
            ],
            'email_blocking' => [
                'title' => ev_gettext('Email Blocking'),
                'parent' => 'main',
            ],
            'link_filters' => [
                'title' => ev_gettext('Link Filters'),
                'parent' => 'main',
            ],
            'field_display' => [
                'title' => ev_gettext('Edit Fields to Display'),
                'parent' => 'main',
            ],
            'column_display' => [
                'title' => ev_gettext('Edit Columns to Display'),
                'parent' => 'main',
            ],
            'status_action_date' => [
                'title' => ev_gettext('Customize Status Action Dates Screen'),
                'parent' => 'main',
            ],
            'segregate_reporter' => [
                'title' => ev_gettext('Segregate Reporters'),
                'parent' => 'main',
            ],
            'permission_levels' => [
                'title' => ev_gettext('User Permission Levels'),
                'parent' => 'main',
            ],
            'ldap' => [
                'title' => ev_gettext('LDAP Authentication'),
                'parent' => 'main',
            ],
        ];

        return self::$topics;
    }

    /**
     * Method used to check whether a specific topic exists or not.
     * This is mainly used in the help documentation main page to see
     * if a requested topic exists, and to show a default one
     * otherwise.
     *
     * @param   string $topic The topic title to check for
     * @return  bool Whether the topic exists or not
     */
    public static function topicExists($topic)
    {
        $topics = self::getTopics();

        if (isset($topics[$topic])) {
            return true;
        }

        return false;
    }

    /**
     * Method used to get the parent help documentation topic
     * associated with a specific topic title.
     *
     * @param   string $topic The topic title
     * @return  array The information related to the parent help topic
     */
    public static function getParent($topic)
    {
        $topics = self::getTopics();

        $child = @$topics[$topic];
        if (empty($child['parent'])) {
            return false;
        }

        return [
                'topic' => $child['parent'],
                'title' => $topics[$child['parent']]['title'],
            ];
    }

    /**
     * Method used to get all the help topics related to a specific
     * 'parent' one.
     *
     * @param   string $topic The 'parent' help topic
     * @return  array The list of help topics
     */
    public static function getChildLinks($topic)
    {
        $topics = self::getTopics();

        $links = [];
        foreach ($topics as $child => $data) {
            if ($data['parent'] == $topic) {
                $links[] = [
                    'topic' => $child,
                    'title' => $data['title'],
                ];
            }
        }
        if (count($links) == 0) {
            return '';
        }

        return $links;
    }

    /**
     * Method used to get all of the navigation links related to a
     * specific help topic.
     *
     * @param   string $topic The topic title
     * @return  array The list of navigation links
     */
    public static function getNavigationLinks($topic)
    {
        $topics = self::getTopics();

        $links = [];
        $links[] = [
            'topic' => '',
            'title' => $topics[$topic]['title'],
        ];
        while ($parent = self::getParent($topic)) {
            $links[] = [
                'topic' => $parent['topic'],
                'title' => $parent['title'],
            ];
            $topic = $parent['topic'];
        }
        $links = array_reverse($links);

        return $links;
    }
}
