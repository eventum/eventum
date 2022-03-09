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

use Doctrine\ORM\Query;
use Eventum\Db\Doctrine;
use Eventum\Export\Serializer\IssueSerializer;
use Eventum\Export\Writer\NdjsonStreamWriter;
use Eventum\Model\Entity\Issue;
use Eventum\ServiceContainer;
use Port\Doctrine\DoctrineReader;
use Port\Reader;
use Port\Steps\Step\ConverterStep;
use Port\Steps\StepAggregator;
use Port\Writer;
use RuntimeException;

class IssueExport
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
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
        $converterStep->add(new IssueSerializer());

        return $converterStep;
    }

    private function createReader(Issue $issue): Reader
    {
        $objectManager = ServiceContainer::getEntityManager();
        $repo = Doctrine::getIssueRepository();

        $reader = new DoctrineReader($objectManager, Issue::class, Query::HYDRATE_OBJECT);
        $reader->setQueryBuilder($repo->createQueryBuilder('o')->andWhere("o.id={$issue->getId()}"));

        return $reader;
    }

    private function createWriter(): Writer
    {
        $directory = sprintf('%s/tree/project', $this->directory);
        $fileName = sprintf('%s/issues.ndjson', $directory);
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
        $stream = fopen($fileName, 'wb');
        if (!$stream) {
            throw new RuntimeException("Can't open {$this->fileName} for writing");
        }

        $writer = new NdjsonStreamWriter($stream);

        return $writer;
    }
}
