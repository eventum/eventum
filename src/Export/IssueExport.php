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

namespace Eventum\Export;

use Eventum\Db\Doctrine;
use Eventum\Model\Entity\Issue;
use Port\Csv\CsvWriter;
use Port\Doctrine\DoctrineReader;
use Port\Reader;
use Port\Steps\Step\ConverterStep;
use Port\Steps\StepAggregator;
use Port\ValueConverter\DateTimeToStringValueConverter;
use Port\Writer;
use RuntimeException;

class IssueExport
{
    /** @var string */
    private $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function export(Issue $issue): void
    {
        $reader = $this->createReader($issue);
        $writer = $this->createWriter();

        $workflow = new StepAggregator($reader);
        $workflow->addWriter($writer);

        $workflow->addStep($this->createConverters());

        $workflow->process();
    }

    private function createConverters(): ConverterStep
    {
        $converterStep = new ConverterStep();
        $dateTimeConverter = new DateTimeToStringValueConverter();
        $converterStep->add(static function (array $item) use ($dateTimeConverter) {
            // The importer is very limited:
            //  Issues can be imported to a project by uploading a CSV file with the columns title and description, in that order.
            //  The user uploading the CSV file will be set as the author of the imported issues.
            // https://docs.gitlab.com/ce/user/project/issues/csv_import.html
            return [
                'Title' => $item['summary'],
                'Description' => $item['description'],
                'Issue ID' => $item['id'],
                'Author' => $item['user_id'],
                'State' => $item['status_id'],
                'Created At (UTC)' => $dateTimeConverter($item['createdDate']),
                'Updated At (UTC)' => $dateTimeConverter($item['updatedDate']),
            ];
        });

        return $converterStep;
    }

    private function createReader(Issue $issue): Reader
    {
        $objectManager = Doctrine::getEntityManager();
        $repo = Doctrine::getIssueRepository();

        $reader = new DoctrineReader($objectManager, Issue::class);
        $reader->setQueryBuilder($repo->createQueryBuilder('o')->andWhere("o.id={$issue->getId()}"));

        return $reader;
    }

    private function createWriter(): Writer
    {
        $stream = fopen($this->fileName, 'wb');
        if (!$stream) {
            throw new RuntimeException("Can't open {$this->fileName} for writing");
        }

        $writer = new CsvWriter($delimiter = ',', $enclosure = '"', $stream, $utf8Encoding = false, $prependHeaderRow = true);

        return $writer;
    }
}
