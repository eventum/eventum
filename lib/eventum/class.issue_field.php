<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
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
//


/**
 * Manages fields that are associated with an issue but can be displayed in many different places.
 *
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Issue_Field
{
    /**
     * Returns an array of field names => titles that this class manages
     *
     * @return array
     */
    public static function getAvailableFields()
    {
        return array(
            'assignee'  =>  'Assignee',
            'priority'  =>  'Priority',
            'severity'  =>  'Severity',
            'custom'    =>  'Custom Fields',
        );
    }


    /**
     * Returns an array of titles, options and current values for the specified
     * display location and issue.
     *
     * @param   integer $issue_id The ID of the issue
     * @param   string  $location The name of the location to display fields
     * @return  array An array of data.
     */
    public static function getDisplayData($issue_id, $location)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $available_fields = self::getAvailableFields();
        $fields = self::getFieldsToDisplay($issue_id, $location);
        $data = array();
        foreach ($fields as $field_name => $field_options) {
            $data[$field_name] = array(
                'title' =>  $available_fields[$field_name],
                'options'   =>  self::getOptions($field_name, $issue_id),
                'value'     =>  self::getValue($issue_id, $field_name),
            );
            if ($field_name == 'custom') {
                $data[$field_name]['custom'] = Custom_Field::getListByIssue($prj_id, $issue_id, Auth::getUserID(), $field_options);
            }
        }
        return $data;
    }


    /**
     * Returns a list of fields that should be displayed in the specified location.
     * A field should be set to false to specifically hide it. If a field is
     * not set a location may choose to use its default.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $location The name of the location
     * @return  array An array of field names.
     */
    public static function getFieldsToDisplay($issue_id, $location)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::getIssueFieldsToDisplay($prj_id, $issue_id, $location);
        return $workflow;
    }


    /**
     * Returns the current value for the specified field / issue. This method just calls
     * the appropriate class / method
     *
     * @param   integer $issue_id
     * @param   integer $field_name
     * @return  mixed
     */
    private static function getValue($issue_id, $field_name)
    {
        switch ($field_name) {
            case 'assignee':
                return Issue::getAssignedUserIDs($issue_id);
            case 'priority':
                return Issue::getPriority($issue_id);
            case 'severity':
                return Issue::getSeverity($issue_id);
        }
        return false;
    }


    /**
     * Sets the value for the specified field / issue. This method just calls the
     * appropriate class / method.
     *
     * @param integer $issue_id
     * @param string $field_name
     * @param mixed $value
     */
    private static function setValue($issue_id, $field_name, $value)
    {
        switch ($field_name) {
            case 'assignee':
                return Issue::setAssignees($issue_id, $value);
            case 'priority':
                return Issue::setPriority($issue_id, $value);
            case 'severity':
                return Issue::setSeverity($issue_id, $value);
        }
    }


    /**
     * Returns the options associated with a specific field
     *
     * @param   string $field_name The name of the field
     * @param   integer $issue_id The ID of the issue
     * @return  array An array of options for the specified field
     */
    private static function getOptions($field_name, $issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        switch ($field_name) {
            case 'assignee':
                $users = Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer'));
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
        return array();
    }


    /**
     * Updates the issue fields for the specified location
     *
     * @param   integer $issue_id
     * @param   string $location The name of the location
     * @param   array $values an array of new values
     */
    public static function updateValues($issue_id, $location, $values)
    {
        $fields = self::getFieldsToDisplay($issue_id, $location);
        foreach ($fields as $field_name => $field_options) {
            if ($field_name == 'custom') {
                Custom_Field::updateValues();
            } else {
                self::setValue($issue_id, $field_name, $values[$field_name]);
            }
        }
    }
}
