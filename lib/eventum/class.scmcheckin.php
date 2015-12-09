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
