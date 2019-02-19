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

namespace Eventum\Controller\Report;

use Report;

class EstimatedDevTimeController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/estimated_dev_time.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * Pad a string to a certain length with another string
     *
     * @param string $s
     * @param int $len
     * @return string
     */
    private function pad($s, $len = 5)
    {
        $s = str_pad($s, $len, ' ', STR_PAD_LEFT);

        return str_replace(' ', '&nbsp;', $s);
    }

    /**
     * Format getEstimatedDevTimeReport for template
     *
     * @return array
     */
    private function getEstimatedDevTimeReport()
    {
        // FIXME: why the nbsp padding if result is used as html where it doesn't matter?

        $res = Report::getEstimatedDevTimeReport($this->prj_id);
        $total = 0;
        foreach ($res as $id => $row) {
            $total += $row['dev_time'];
            $res[$id]['dev_time'] = $this->pad($row['dev_time']);
        }
        $res[] = [
            'dev_time' => $this->pad($total),
            'prc_title' => 'Total',
        ];

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign('data', $this->getEstimatedDevTimeReport());
    }
}
