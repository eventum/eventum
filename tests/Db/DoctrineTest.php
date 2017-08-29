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
use Eventum\Test\TestCase;

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
        $em = $this->getEntityManager();
        $repo = $em->getRepository(Entity\Commit::class);
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
        $em = $this->getEntityManager();
        $repo = $em->getRepository(Entity\Commit::class);
        $qb = $repo->createQueryBuilder('commit');

        $qb->setMaxResults(10);

        $query = $qb->getQuery();
        $items = $query->getArrayResult();

        print_r($items);
    }

    public function test4()
    {
        $em = $this->getEntityManager();

        $issue_id = 1;
        $changeset = uniqid('z1', true);
        $ci = Entity\Commit::create()
            ->setScmName('cvs')
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setChangeset($changeset)
            ->setMessage('Mes-Sage');
        $em->persist($ci);
        $em->flush();

        $cf = Entity\CommitFile::create()
            ->setCommitId($ci->getId())
            ->setFilename('file');
        $em->persist($cf);
        $em->flush();

        $isc = Entity\IssueCommit::create()
            ->setCommitId($ci->getId())
            ->setIssueId($issue_id);
        $em->persist($isc);
        $em->flush();

        printf(
            "ci: %d\ncf: %d\nisc: %d\n",
            $ci->getId(), $cf->getId(), $isc->getId()
        );
    }

    public function test5()
    {
        $em = $this->getEntityManager();
        $project = $em->getRepository(Entity\Project::class);
    }

    private function getEntityManager()
    {
        return Doctrine::getEntityManager();
    }
}
