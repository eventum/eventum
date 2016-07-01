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

use Eventum\Db\Adapter\AdapterInterface;

/**
 * Set required fields to match old default configuration
 */

/** @var AdapterInterface $db */

$setup = Setup::get();

// check if the table has any entries. If so, update, otherwise create defaults.
// do this project by project in case additional projects were never setup
$projects = Project::getAll();
foreach ($projects as $prj_id => $prj_title) {
    if ($db->getOne('SELECT count(*) FROM {{%project_field_display}} WHERE pfd_prj_id = ?', [$prj_id]) > 0) {
        $db->query("UPDATE
                        {{%project_field_display}}
                    SET
                        pfd_required = 1
                    WHERE
                      pfd_field IN('category', 'priority', 'severity') AND
                      pfd_prj_id = ?", [$prj_id]);
        if (isset($setup['allow_unassigned_issues']) && $setup['allow_unassigned_issues'] != 'yes') {
            $db->query("UPDATE
                            {{%project_field_display}}
                        SET
                            pfd_required = 1
                        WHERE
                          pfd_field = 'assignment' AND
                          pfd_prj_id = ?", [$prj_id]);
        }
    } else {
        Project::setDefaultDisplayFields($prj_id);
    }
}
