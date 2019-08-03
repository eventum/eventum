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

namespace Eventum\Controller\Helper;

use Auth;
use Custom_Field;
use Date_Helper;
use Eventum\Config\Paths;
use Eventum\Session;
use PHPlot;
use Prefs;
use Project;
use Report;
use Stats;
use Status;
use User;

class PlotHelper
{
    /**
     * Path to truetype fonts
     *
     * @see PHPlot::GetDefaultTTFont
     * @var string
     */
    private $fonts_path = Paths::APP_FONTS_PATH;

    /**
     * Create PHPlot instance initializing common options
     *
     * @param int $width
     * @param int $height
     * @return PHPlot
     */
    private function create($width, $height)
    {
        $plot = new PHPlot($width, $height);
        $plot->SetTTFPath($this->fonts_path);
        $plot->SetUseTTF(true);

        return $plot;
    }

    /**
     * Plot various stats charts
     *
     * @param string $plotType
     * @param bool $hide_closed
     * @return bool return false if no data is available
     */
    public function StatsChart($plotType, $hide_closed)
    {
        // don't bother if user has no access
        $prj_id = Auth::getCurrentProject();
        if (Auth::getCurrentRole() <= User::ROLE_REPORTER && Project::getSegregateReporters($prj_id)) {
            return false;
        }

        $colors = [];

        switch ($plotType) {
            case 'status':
                $data = Stats::getAssocStatus($hide_closed);
                $graph_title = ev_gettext('Issues by Status');
                // use same colors as defined for statuses
                foreach ($data as $sta_title => $trash) {
                    $sta_id = Status::getStatusID($sta_title);
                    $status_details = Status::getDetails($sta_id);
                    $colors[] = $status_details['sta_color'];
                }
                break;
            case 'release':
                $data = Stats::getAssocRelease($hide_closed);
                $graph_title = ev_gettext('Issues by Release');
                break;
            case 'priority':
                $data = Stats::getAssocPriority($hide_closed);
                $graph_title = ev_gettext('Issues by Priority');
                break;
            case 'user':
                $data = Stats::getAssocUser($hide_closed);
                $graph_title = ev_gettext('Issues by Assignment');
                break;
            case 'category':
                $data = Stats::getAssocCategory($hide_closed);
                $graph_title = ev_gettext('Issues by Category');
                break;
            default:
                return false;
        }

        // check the values coming from the database and if they are all empty, then
        // output a pre-generated 'No Data Available' picture
        if (!Stats::hasData($data)) {
            return false;
        }

        $plot = $this->create(360, 200);
        $plot->SetImageBorderType('plain');
        $plot->SetTitle($graph_title);
        $plot->SetPlotType('pie');
        $plot->SetDataType('text-data-single');
        if ($colors) {
            $plot->SetDataColors($colors);
        }

        $legend = $dataValue = [];
        foreach ($data as $label => $count) {
            $legend[] = $label . ' (' . $count . ')';
            $dataValue[] = [$label, $count];
        }

        $plot->SetDataValues($dataValue);

        foreach ($legend as $label) {
            $plot->SetLegend($label);
        }

        return $plot->DrawGraph();
    }

    /**
     * Generates a graph for the selected custom field.
     *
     * @param string $type
     * @param int $custom_field the id of the custom field
     * @param array $custom_options an array of option ids
     * @param string $group_by how the data should be grouped
     * @param string $start
     * @param string $end
     * @param string $interval
     * @return bool
     */
    public function CustomFieldGraph($type, $custom_field, $custom_options, $group_by, $start, $end, $interval)
    {
        $data = Report::getCustomFieldReport($custom_field, $custom_options, $group_by, $start, $end, false, $interval);

        if (count($data) < 2) {
            return false;
        }

        $field_details = Custom_Field::getDetails($custom_field);

        // convert to phplot format
        $i = 0;
        $plotData = $labels = [];
        unset($data['All Others']);
        foreach ($data as $label => $value) {
            $plotData[$i] = [$label, $value];
            $labels[] = $label;
            $i++;
        }

        if ($type == 'pie') {
            $plot = $this->create(500, 300);
            $plot->SetPlotType('pie');
            $plot->SetDataType('text-data-single');
        } else {
            // bar chart
            $plot = $this->create(500, 350);
            $plot->SetPlotType('bars');
            $plot->SetDataType('text-data');
            $plot->SetXTitle($field_details['fld_title']);
            $plot->SetYTitle(ev_gettext('Issue Count'));
            $plot->SetXTickLabelPos('none');
            $plot->SetXTickPos('none');
            $plot->SetYDataLabelPos('plotin');
        }

        if ($group_by == 'customers') {
            $title = ev_gettext('Customers by %s', $field_details['fld_title']);
        } else {
            $title = ev_gettext('Issues by %s', $field_details['fld_title']);
        }

        $plot->SetDataValues($plotData);
        $plot->SetLegend($labels);
        $plot->SetImageBorderType('plain');
        $plot->SetTitle($title);

        return $plot->DrawGraph();
    }

    /**
     * Generates the workload by time period graph.
     *
     * @param string $type
     */
    public function WorkloadTimePeriodGraph($type): void
    {
        $usr_id = Auth::getUserID();

        $timezone = Prefs::getTimezone($usr_id);

        if ($type === 'email') {
            $data = Report::getEmailWorkloadByTimePeriod($timezone, true);
            $graph_title = ev_gettext('Email by Time Period');
            $event_type = ev_gettext('emails');
        } else {
            $data = Report::getWorkloadByTimePeriod($timezone, true);
            $graph_title = ev_gettext('Workload by Time Period');
            $event_type = ev_gettext('actions');
        }

        // TRANSLATORS: %s = Timezone name
        $xtitle = ev_gettext('Hours (%s)', Date_Helper::getTimezoneShortNameByUser($usr_id));

        // rebuild data for phplot format
        $plotData = [];
        $legends = [];

        $i = 1;
        foreach ($data as $performer => $values) {
            foreach ($values as $hour => $value) {
                $plotData[(int)$hour][0] = $hour;
                $plotData[(int)$hour][$i] = $value;
            }
            $legends[$i] = ucfirst($performer) . ' ' . $event_type;
            $i++;
        }

        $plot = $this->create(900, 350);
        $plot->SetImageBorderType('plain');
        $plot->SetPlotType('bars');
        $plot->SetDataType('text-data');
        $plot->SetDataValues($plotData);
        $plot->SetTitle($graph_title);
        $plot->SetLegend($legends);
        $plot->SetYTitle($event_type);
        $plot->SetXTitle($xtitle);
        $plot->SetXTickLabelPos('none');
        $plot->SetXTickPos('none');
        $plot->SetYDataLabelPos('plotin');
        $plot->SetYLabelType('printf', '%.0f%%');
        $plot->group_frac_width = 1;
        $plot->DrawGraph();
    }

    /**
     * Generates a graph for workload by date range report.
     *
     * @param string $graph
     * @param string $type
     * @param string $start_date
     * @param string $end_date
     * @param $interval
     * @return bool
     */
    public function WorkloadDateRangeGraph($graph, $type, $start_date, $end_date, $interval)
    {
        $data = Session::get('workload_date_range_data');
        if (empty($data)) {
            return false;
        }

        switch ($interval) {
            case 'dow':
                $x_title = ev_gettext('Day of Week');
                break;
            case 'week':
                $x_title = ev_gettext('Week');
                break;
            case 'dom':
                $x_title = ev_gettext('Day of Month');
                break;
            case 'day':
                $x_title = ev_gettext('Day');
                break;
            case 'month':
                $x_title = ev_gettext('Month');
                break;
            default:
                return false;
        }

        switch ($graph) {
            case 'issue':
                $plots = array_values($data['issues']['points']);
                $graph_title = ev_gettext('Issues by created date %s through %s', $start_date, $end_date);
                $labels = array_keys($data['issues']['points']);
                $y_label = ev_gettext('Issues');
                break;

            case 'email':
                $plots = array_values($data['emails']['points']);
                $graph_title = ev_gettext('Emails by sent date %s through %s', $start_date, $end_date);
                $labels = array_keys($data['emails']['points']);
                $y_label = ev_gettext('Emails');
                break;

            case 'note':
                $plots = array_values($data['notes']['points']);
                $graph_title = ev_gettext('Notes by sent date %s through %s', $start_date, $end_date);
                $labels = array_keys($data['notes']['points']);
                $y_label = ev_gettext('Notes');
                break;

            case 'phone':
                $plots = array_values($data['phone']['points']);
                $graph_title = ev_gettext('Phone calls by date %s through %s', $start_date, $end_date);
                $labels = array_keys($data['phone']['points']);
                $y_label = ev_gettext('Phone Calls');
                break;

            case 'time_spent':
                $plots = array_values($data['time_spent']['points']);
                $graph_title = ev_gettext('Time spent (hrs) %s through %s', $start_date, $end_date);
                $labels = array_keys($data['time_spent']['points']);
                $y_label = ev_gettext('Hours');
                break;

            case 'avg_time_per_issue':
                $plots = array_values($data['avg_time_per_issue']['points']);
                $graph_title = ev_gettext('Avg. Time spent per issue (min) %s through %s', $start_date, $end_date);
                $labels = array_keys($data['avg_time_per_issue']['points']);
                $y_label = ev_gettext('Minutes');
                break;

            default:
                return false;
        }

        if (count($plots) < 1) {
            return false;
        }

        // convert to phplot format
        $plotData = [];
        foreach ($plots as $i => $plot) {
            $plotData[] = [$labels[$i], $plot];
        }

        if ($type == 'pie') {
            $plot = $this->create(500, 300);
            $plot->SetPlotType('pie');
            $plot->SetDataType('text-data-single');
            $plot->SetLegend($labels);
        } else {
            $plot = $this->create(500, 350);
            $plot->SetPlotType('bars');
            $plot->SetDataType('text-data');
            $plot->SetYTitle($y_label);
            $plot->SetXTitle($x_title);
            $plot->SetYDataLabelPos('plotin');
        }

        $plot->SetTitle($graph_title);
        $plot->SetImageBorderType('plain');
        $plot->SetDataValues($plotData);

        return $plot->DrawGraph();
    }
}
