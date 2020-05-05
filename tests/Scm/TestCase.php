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

namespace Eventum\Test\Scm;

use Date_Helper;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Extension\ExtensionManager;
use Eventum\Model\Entity;
use Eventum\Model\Repository\StatusRepository;
use Eventum\ServiceContainer;
use Eventum\Test\Traits\DoctrineTrait;

use ProjectSeeder;
use Setup;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;
use UserSeeder;

abstract class TestCase extends WebTestCase
{
    use DoctrineTrait;

    public static function setUpBeforeClass(): void
    {
        self::setUpConfig();

        // Boot ExtensionManager
        // current test touches parts that would require workflow to be called
        ServiceContainer::get(ExtensionManager::class);
    }

    /**
     * Create commit associated to new issue
     */
    protected function createCommit(string $scm = 'cvs'): Entity\Commit
    {
        $changeset = uniqid('z1', false);

        $ci = (new Entity\Commit())
            ->setScmName($scm)
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setChangeset($changeset)
            ->setMessage('Mes-Sage')
            ->addFile(
                (new Entity\CommitFile())
                    ->setFilename('file')
            );

        $issue = $this->createIssue('issue with commits');
        $issue->addCommit($ci);

        return $ci;
    }

    private function createIssue(string $title): Entity\Issue
    {

        /** @var StatusRepository $sr */
        $sr = $this->getEntityManager()->getRepository(Entity\Status::class);

        $issue = new Entity\Issue();
        $issue->setSummary($title);
        $issue->setDescription($title);
        $issue->setProjectId(ProjectSeeder::DEFAULT_PROJECT_ID);
        $issue->setUserId(UserSeeder::ACTIVE_ACCOUNT);
        $issue->setStatus($sr->findByTitle('discovery'));

        return $issue;
    }

    protected function flushCommit(Entity\Commit $commit): Entity\Commit
    {
        $em = $this->getEntityManager();

        // persisting issue will cascade save of issue_commit, commit, commit_file com
        $em->persist($commit->getIssue());
        $em->flush();

        // flush cache to ensure doctrine has to fetch from db
        foreach ($commit->getFiles() as $file) {
            $em->detach($file);
        }
        $em->detach($commit);
        $em->detach($commit->getIssue());

        return $commit;
    }

    protected function addFilesListener(array &$files): void
    {
        $listener = static function (GenericEvent $event) use (&$files): void {
            /** @var Entity\Commit $commit */
            $commit = $event->getSubject();
            foreach ($commit->getFiles() as $cf) {
                $files[] = $cf->getFilename();
            }
        };

        $dispatcher = EventManager::getEventDispatcher();
        $dispatcher->addListener(SystemEvents::SCM_COMMIT_ASSOCIATED, $listener);
    }

    private static function setUpConfig(): void
    {
        $scm = [
            'cvs' => [
                'name' => 'cvs',
                'checkout_url' => 'https://localhost/{MODULE}/{FILE}?rev={NEW_VERSION}&content-type=text/x-cvsweb-markup',
                'diff_url' => 'https://localhost/{MODULE}/{FILE}?r1={OLD_VERSION}&r2={NEW_VERSION}&f=h',
                'log_url' => 'https://localhost/{MODULE}/{FILE}?r1={VERSION}#rev{VERSION}',
            ],
            'gitlab' => [
                'name' => 'gitlab',
                'urls' => [
                    'http://localhost:10080',
                    'git@localhost',
                ],
                'only' => ['merge-tip'],
                'except' => ['dev'],
                'checkout_url' => 'http://localhost:10080/{PROJECT}/blob/{VERSION}/{FILE}',
                'diff_url' => 'http://localhost:10080/{PROJECT}/commit/{VERSION}#{FILE}',
                'log_url' => 'http://localhost:10080/{PROJECT}/commits/{VERSION}/{FILE}',
            ],
        ];

        Setup::set(['scm' => $scm]);
    }
}
