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
use Eventum\Extension\ExtensionManager;
use Eventum\Model\Entity;
use Eventum\Test\TestCase;
use Project;
use SCM;
use Setup;
use Workflow;

class ScmTestCase extends TestCase
{
    public static function setUpBeforeClass()
    {
        self::setUpConfig();

        // Boot ExtensionManager
        // current test touches parts that would require workflow to be called
        ExtensionManager::getManager();
    }

    /**
     * Create commit associated to new issue
     *
     * @return Entity\Commit
     */
    protected function createCommit($scm = 'cvs')
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

        $issue = new Entity\Issue();
        $issue->setSummary('issue with commits');
        $issue->setProjectId(1);
        $issue->addCommit($ci);

        return $ci;
    }

    protected function flushCommit(Entity\Commit $commit)
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

    private static function setUpConfig()
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
