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

namespace Eventum\Controller\Manage;

use Reminder;

class ReminderReviewController extends ManageBaseController
{
    /** @var int */
    private $rem_id;

    /** @var int */
    private $rma_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->rem_id = $request->query->getInt('rem_id');
        $this->rma_id = $request->query->getInt('rma_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $sql = Reminder::getSQLQuery($this->rem_id, $this->rma_id);

        echo "<span class='default'>";
        echo '<b>The following is the SQL statement produced by this reminder:</b><br /><br />';
        echo nl2br($sql);

        echo '<br /><br />';
        echo '<a class="link" href="javascript:window.close();">Close Window</a>';
        echo '</span>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
