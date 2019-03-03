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
 * Manages fields that are associated with an issue but can be displayed in many different places.
 */
class Issue_Field
{
    public const LOCATION_POST_NOTE = 'post_note';
    public const LOCATION_VIEW_ISSUE = 'view_issue';

    /**
     * Returns an array of field names => titles that this class manages
     */
    public static function getAvailableFields(): array
    {
        return [
            'assignee' => 'Assignee',
            'priority' => 'Priority',
            'severity' => 'Severity',
            'custom' => 'Custom Fields',
        ];
    }

    /**
     * Returns an array of titles, options and current values for the specified
     * display location and issue.
     *
     * @param   int $issue_id The ID of the issue
     * @param   string  $location The name of the location to display fields
     * @return  array an array of data
     */
    public static function getDisplayData(int $issue_id, string $location): array
    {
        $prj_id = Issue::getProjectID($issue_id);
        $available_fields = self::getAvailableFields();
        $fields = self::getFieldsToDisplay($issue_id, $location);
        $usr_id = Auth::getUserID();
        $data = [];
        foreach ($fields as $field_name => $field_options) {
            $data[$field_name] = [
                'title' => $available_fields[$field_name],
                'options' => self::getOptions($field_name, $issue_id),
                'value' => self::getValue($issue_id, $field_name),
            ];
            if ($field_name === 'custom') {
                $data[$field_name]['custom'] = Custom_Field::getListByIssue($prj_id, $issue_id, $usr_id, $field_options, true);
            }
        }

        return $data;
    }

    /**
     * Returns a list of fields that should be displayed in the specified location.
     * A field should be set to false to specifically hide it. If a field is
     * not set a location may choose to use its default.
     *
     * @param   int $issue_id The issue ID
     * @param   string $location The name of the location
     * @return  array an array of field names
     */
    public static function getFieldsToDisplay($issue_id, $location): array
    {
        $prj_id = Issue::getProjectID($issue_id);

        return Workflow::getIssueFieldsToDisplay($prj_id, $issue_id, $location);
    }

    /**
     * Returns the current value for the specified field / issue. This method just calls
     * the appropriate class / method
     *
     * @param   int $issue_id
     * @param   string $field_name
     * @return  array|int|null
     */
    private static function getValue(int $issue_id, string $field_name)
    {
        switch ($field_name) {
            case 'assignee':
                return Issue::getAssignedUserIDs($issue_id);
            case 'priority':
                return Issue::getPriority($issue_id);
            case 'severity':
                return Issue::getSeverity($issue_id);
        }

        return null;
    }

    /**
     * Sets the value for the specified field / issue. This method just calls the
     * appropriate class / method.
     *
     * @param int $issue_id
     * @param string $field_name
     * @param string|array $value
     * @return int|null
     */
    private static function setValue($issue_id, $field_name, $value): ?int
    {
        switch ($field_name) {
            case 'assignee':
                return Issue::setAssignees($issue_id, $value);
            case 'priority':
                return Issue::setPriority($issue_id, $value);
            case 'severity':
                return Issue::setSeverity($issue_id, $value);
        }

        return null;
    }

    /**
     * Returns the options associated with a specific field
     *
     * @param   string $field_name The name of the field
     * @param   int $issue_id The ID of the issue
     * @return  array An array of options for the specified field
     */
    private static function getOptions(string $field_name, int $issue_id): array
    {
        $prj_id = Issue::getProjectID($issue_id);
        switch ($field_name) {
            case 'assignee':
                $users = Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER);
                $current_assignees = Issue::getAssignedUserIDs($issue_id);
                foreach ($current_assignees as $usr_id) {
                    if (!isset($users[$usr_id])) {
                        $users[$usr_id] = User::getFullName($usr_id);
                    }
                    asort($users);
                }

                return $users;
            case 'priority':
                return Priority::getAssocList($prj_id);
            case 'severity':
                return Severity::getAssocList($prj_id);
        }

        return [];
    }

    /**
     * Updates the issue fields for the specified location
     *
     * @param   int $issue_id
     * @param   string $location The name of the location
     * @param   array $values an array of new values
     */
    public static function updateValues(int $issue_id, string $location, array $values): void
    {
        $fields = self::getFieldsToDisplay($issue_id, $location);
        foreach ($fields as $field_name => $field_options) {
            if ($field_name === 'custom') {
                Custom_Field::updateFromPost();
            } else {
                self::setValue($issue_id, $field_name, $values[$field_name]);
            }
        }
    }
}
