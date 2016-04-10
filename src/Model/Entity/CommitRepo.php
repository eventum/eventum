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

namespace Eventum\Model\Entity;

use InvalidArgumentException;
use Setup;

/**
 * Class Eventum\Model\Entity\CommitRepo
 */
class CommitRepo
{
    private $checkout_url;
    private $diff_url;
    private $log_url;

    /** @var array */
    private $config;

    public function __construct($name)
    {
        $setup = Setup::get();

        if (!isset($setup['scm'][$name])) {
            throw new InvalidArgumentException("SCM Repo '$name' not defined");
        }

        $this->config = $setup['scm'][$name];
    }

    public function getCheckoutUrl($checkin)
    {
        return $this->parseURL($this->config['checkout_url'], $checkin);
    }

    public function getDiffUrl($checkin)
    {
        return $this->parseURL($this->config['diff_url'], $checkin);
    }

    public function getLogUrl($checkin)
    {
        return $this->parseURL($this->config['log_url'], $checkin);
    }

    public function getChangesetUrl(Commit $commit)
    {
        $changeset = $commit->getChangeset();
        return str_replace('{CHANGESET}', $changeset, $this->config['changeset_url']);
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
        $url = str_replace('{MODULE}', $checkin['project_name'], $url);
        $url = str_replace('{FILE}', $checkin['cof_filename'], $url);
        $url = str_replace('{OLD_VERSION}', $checkin['cof_old_version'], $url);
        $url = str_replace('{NEW_VERSION}', $checkin['cof_new_version'], $url);

        // the current version to look log from
        if ($checkin['added']) {
            $url = str_replace('{VERSION}', $checkin['cof_new_version'], $url);
        } elseif ($checkin['removed']) {
            $url = str_replace('{VERSION}', $checkin['cof_old_version'], $url);
        } else {
            $url = str_replace('{VERSION}', $checkin['cof_new_version'], $url);
        }

        return $url;
    }
}
