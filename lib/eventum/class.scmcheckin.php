<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2015 Eventum Team.                                |
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
 * Class ScmCheckin
 */
class ScmCheckin
{
    private $name;
    private $checkout_url;
    private $diff_url;
    private $log_url;

    public function __construct($config)
    {
        $this->name = $config['name'];
        $this->checkout_url = $config['checkout_url'];
        $this->diff_url = $config['diff_url'];
        $this->log_url = $config['log_url'];
    }

    public function getCheckoutUrl($checkin)
    {
        return $this->parseURL($this->checkout_url, $checkin);
    }

    public function getDiffUrl($checkin)
    {
        return $this->parseURL($this->diff_url, $checkin);
    }

    public function getLogUrl($checkin)
    {
        return $this->parseURL($this->log_url, $checkin);
    }

    /**
     * Method used to parse an user provided URL and substitute a known set of
     * placeholders for the appropriate information.
     *
     * @param   string $url The user provided URL
     * @return  string The parsed URL
     */
    private function parseURL($url, $checkin)
    {
        $url = str_replace('{MODULE}', $checkin['isc_module'], $url);
        $url = str_replace('{FILE}', $checkin['isc_filename'], $url);
        $url = str_replace('{OLD_VERSION}', $checkin['isc_old_version'], $url);
        $url = str_replace('{NEW_VERSION}', $checkin['isc_new_version'], $url);

        // the current version to look log from
        if ($checkin['added']) {
            $url = str_replace('{VERSION}', $checkin['isc_new_version'], $url);
        } elseif ($checkin['removed']) {
            $url = str_replace('{VERSION}', $checkin['isc_old_version'], $url);
        } else {
            $url = str_replace('{VERSION}', $checkin['isc_new_version'], $url);
        }

        return $url;
    }
}
