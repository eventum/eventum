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
use Eventum\Model\Repository\ProjectRepository;
use Eventum\Model\Repository\UserRepository;
use Eventum\Test\TestCase;

/**
 * TODO: datetime and timezone: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/working-with-datetime.html
 *
 * @group db
 */
class DoctrineTest extends TestCase
{
    public function testFindAll()
    {
        $repo = $this->getEntityManager()->getRepository(Entity\Project::class);
        $projects = $repo->findAll();

        /**
         * @var Entity\Project $project
         */
        foreach ($projects as $project) {
            printf("#%d: %s\n", $project->getId(), $project->getTitle());
        }
    }

    public function test2()
    {
        $repo = Doctrine::getCommitRepository();
        $items = $repo->findBy([], null, 10);

        /**
         * @var Entity\Commit $item
         */
        foreach ($items as $item) {
            printf("* %s %s\n", $item->getId(), trim($item->getMessage()));
        }
    }

    public function test3()
    {
        $repo = Doctrine::getCommitRepository();
        $qb = $repo->createQueryBuilder('commit');

        $qb->setMaxResults(10);

        $query = $qb->getQuery();
        $items = $query->getArrayResult();

        print_r($items);
    }

    public function testDeleteByQuery()
    {
        $issue_id = 13;
        $associated_issue_id = 12;
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->delete(Entity\IssueAssociation::class, 'ia');

        $expr = $qb->expr();
        $left = $expr->andX('ia.isa_issue_id = :isa_issue_id', 'ia.isa_associated_id = :isa_associated_id');
        $right = $expr->andX('ia.isa_issue_id = :isa_associated_id', 'ia.isa_associated_id = :isa_issue_id');
        $qb->where(
            $expr->orX()
                ->add($left)
                ->add($right)
        );

        $qb->setParameter('isa_issue_id', $issue_id);
        $qb->setParameter('isa_associated_id', $associated_issue_id);
        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function testProjectStatusId()
    {
        /** @var ProjectRepository $repo */
        $repo = $this->getEntityManager()->getRepository(Entity\Project::class);
        $prj_id = 1;
        $status_id = $repo->findById($prj_id)->getInitialStatusId();
        dump($status_id);

        $status_id = Doctrine::getProjectRepository()->findById($prj_id)->getInitialStatusId();
        dump($status_id);
    }

    public function testUserModel()
    {
        $repo = $this->getEntityManager()->getRepository(Entity\User::class);
        $items = $repo->findBy([], null, 1);

        dump($items);
    }

    public function testIssueRepository()
    {
        $em = Doctrine::getEntityManager();
        $repo = $em->getRepository(Entity\Issue::class);

        $issue = $repo->findOneBy(['id' => 135]);
        $this->assertNotNull($issue);

        $commitCollection = $issue->getCommits();
        $commits = iterator_to_array($commitCollection);
        $this->assertCount(12, $commits);

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

    public function testIssueAddCommit()
    {
        $em = Doctrine::getEntityManager();
        $repo = $em->getRepository(Entity\Issue::class);

        /** @var Entity\Issue $issue */
        $issue = $repo->findOneBy(['id' => 64]);
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

    public function testAddCommit()
    {
        $issue_id = 1;

        $issue = new Entity\Issue();
        $issue->setId(1);
    }

    public function testUserRepository()
    {
        /** @var UserRepository $repo */
        $repo = $this->getEntityManager()->getRepository(Entity\User::class);

        $user = $repo->findOneByCustomerContactId(1);
        dump($user);
        $user = $repo->findByContactId(1);
        dump($user);

        // find by id
        $user = $repo->find(-1);
        dump($user);

        // query for a single product matching the given name and price
        $user = $repo->findOneBy(
            ['status' => 'active', 'partnerCode' => 0]
        );

        // query for multiple products matching the given name, ordered by price
        $users = $repo->findBy(
            ['status' => 'inactive'],
            ['id' => 'ASC']
        );
    }
}
