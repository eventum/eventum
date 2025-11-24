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
use Psr\Log\LoggerInterface;

class IssueExport
{
    /** @var string */
    private $directory;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $directory, LoggerInterface $logger)
    {
        $this->directory = $directory;
        $this->logger = $logger;
    }

    /**
     * @param Issue[] $issues
     */
    public function export(array $issues): void
    {
        $reader = $this->createReader($issues);
        $writer = $this->createWriter();

        $workflow = new StepAggregator($reader);
        $workflow->addWriter($writer);

        $workflow->addStep($this->createConverters());

        $workflow->process();
    }

    private function createConverters(): ConverterStep
    {
        $converterStep = new ConverterStep();
        $converterStep->add(new IssueSerializer($this->logger));

        return $converterStep;
    }

    private function createReader(array $issues): Reader
    {
        $objectManager = ServiceContainer::getEntityManager();
        $repo = Doctrine::getIssueRepository();

        $reader = new DoctrineReader($objectManager, Issue::class, Query::HYDRATE_OBJECT);

        $queryBuilder = $repo->createQueryBuilder('o');
        $issueIds = array_map(static function (Issue $issue) {
            return $issue->getId();
        }, $issues);
        if ($issueIds) {
            $queryBuilder
                ->andWhere('o.id in (:ids)')
                ->setParameter('ids', $issueIds);
        }
        $reader->setQueryBuilder($queryBuilder);

        return $reader;
    }

    private function createWriter(): Writer
    {
        $fileName = sprintf('%s/tree/project/issues.ndjson', $this->directory);
        $stream = FileUtil::createWritableStream($fileName);

        $writer = new NdjsonStreamWriter($stream);

        return $writer;
    }
}
