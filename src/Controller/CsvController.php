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

namespace Eventum\Controller;

use Access;
use Auth;
use Misc;

class CsvController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $usr_id = Auth::getUserID();

        if (!Access::canExportData($usr_id)) {
            // TODO: better exit state
            exit;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $post = $this->getRequest()->request;
        $csv = base64_decode($post->get('csv_data'));

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Pragma: no-cache');
        header('Cache-Control: must-revalidate, post-check=0,pre-check=0');

        $filename = uniqid('csv') . '.xls';
        $mimetype = 'application/vnd.ms-excel';
        $filesize = Misc::countBytes($csv);
        Misc::outputDownload($csv, $filename, $filesize, $mimetype);
        exit;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
