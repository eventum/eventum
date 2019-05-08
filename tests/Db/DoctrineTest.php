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

namespace Eventum\Test\Db;

use Date_Helper;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Eventum\Model\Entity\Issue;
use Eventum\Test\TestCase;
use Eventum\Test\Traits\DoctrineTrait;
use IssueSeeder;

/**
 * TODO: datetime and timezone: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/working-with-datetime.html
 *
 * @group db
 */
class DoctrineTest extends TestCase
{
    use DoctrineTrait;

    public function testIssueCommits(): void
    {
        $repo = $this->getEntityManager()->getRepository(Entity\Issue::class);

        $issue = $repo->findOneBy(['id' => IssueSeeder::ISSUE_1]);
        $this->assertNotNull($issue);

        $commitCollection = $issue->getCommits();
        $commits = iterator_to_array($commitCollection);
        $this->assertCount(1, $commits);

        /** @var Entity\Commit $commit */
        $commit = $commits[0];
        $this->assertInstanceOf(Entity\Commit::class, $commit);

        $fileCollection = $commit->getFiles();
        $files = iterator_to_array($fileCollection);
        $this->assertCount(1, $files);

        /** @var Entity\CommitFile $file */
        $file = $files[0];
        $this->assertInstanceOf(Entity\CommitFile::class, $file);
    }

    public function testIssueAddCommit(): void
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(Entity\Issue::class);

        /** @var Entity\Issue $issue */
        $issue = $repo->findOneBy(['id' => IssueSeeder::ISSUE_2]);
        $this->assertNotNull($issue);

        $changeset = uniqid('z1', false);
        $commit = (new Entity\Commit())
            ->setScmName('cvs')
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setChangeset($changeset)
            ->setMessage('Mes-Sage');

        $cf = (new Entity\CommitFile())
            ->setCommit($commit)
            ->setFilename('file');

        $issue->addCommit($commit);

        $em->persist($cf);
        $em->persist($commit);
        $em->persist($issue);
        $em->flush();
    }

    public function testIssueStatus(): void
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(Entity\Issue::class);

        /** @var Issue $issue */
        $issue = $repo->findOneBy(['id' => IssueSeeder::ISSUE_1]);
        $this->assertNotNull($issue);

        $this->assertEquals(IssueSeeder::STATUS_DISCOVERY, $issue->getStatus()->getId());
    }

    public function testStatus(): void
    {
        $repo = Doctrine::getStatusRepository();

        $status = $repo->findById(IssueSeeder::STATUS_DISCOVERY);
        $this->assertEquals('discovery', $status->getTitle());

        $status = $repo->findByTitle('discovery');
        $this->assertEquals(IssueSeeder::STATUS_DISCOVERY, $status->getId());
        $this->assertEquals('DSC', $status->getAbbreviation());
    }
}
