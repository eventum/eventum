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

use Eventum\Db\Doctrine;
use Eventum\Export\IssueExport;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportIssuesCommand extends BaseCommand
{
    public const DEFAULT_COMMAND = 'export:issues';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::OPTIONAL, 'Output directory', '.')
            ->addOption('issueId', null, InputOption::VALUE_REQUIRED, 'Issue Id to export')
            ->setDescription('Export Issues to GitLab export format');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');
        $issueId = $input->getOption('issueId');

        if ($issueId) {
            $this->exportIssue($directory, $issueId);
        }

        return 0;
    }

    private function exportIssue(string $directory, int $issueId): void
    {
        $repo = Doctrine::getIssueRepository();
        $issue = $repo->findById($issueId);

        $exporter = new IssueExport($directory);
        $exporter->export($issue);
    }
}
