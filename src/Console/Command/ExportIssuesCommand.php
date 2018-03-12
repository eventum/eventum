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

namespace Eventum\Console\Command;

use Eventum\GitlabExportWriter;
use Symfony\Component\Console\Output\OutputInterface;

class ExportIssuesCommand extends Command
{
    const DEFAULT_COMMAND = 'export:issues';
    const USAGE = self::DEFAULT_COMMAND . ' [issueId]';

    public function execute(OutputInterface $output, $issueId)
    {
        $this->output = $output;
        $this->exportIssue($issueId);
    }

    private function exportIssue($issueId)
    {
        $writer = new GitlabExportWriter();
    }
}
