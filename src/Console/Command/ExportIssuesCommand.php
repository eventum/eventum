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
use Eventum\Export\GitlabExportWriter;
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
            ->addOption('issueId', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Issue Id to export')
            ->setDescription('Export Issues to GitLab export format');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument('directory');
        $issueIds = $input->getOption('issueId');

        $this->exportIssue($directory, $issueIds);
        $output->writeln("Exported to directory <info>{$directory}</>");
        $command = sprintf('tar -C %s -czf %s.tar.gz .', $directory, basename($directory));
        $output->writeln("You can create tar.gz with <info>{$command}</>");

        return 0;
    }

    private function exportIssue(string $directory, array $issueIds): void
    {
        $writer = new GitlabExportWriter($directory);
        $writer->export();

        $repo = Doctrine::getIssueRepository();
        $issues = $repo->findBy(['id' => $issueIds]);

        $exporter = new IssueExport($directory);
        $exporter->export($issues);
    }
}
