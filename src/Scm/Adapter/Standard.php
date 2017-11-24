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

namespace Eventum\Scm\Adapter;

use Eventum\Db\Doctrine;
use Eventum\Scm\Payload\StandardPayload;
use InvalidArgumentException;
use Issue;
use Symfony\Component\HttpFoundation\Request;

/**
 * Standard SCM handler
 */
class Standard extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function can()
    {
        // must be POST
        if ($this->request->getMethod() !== Request::METHOD_POST) {
            return false;
        }

        // require 'scm' GET parameter to be 'svn' or 'git'
        return in_array($this->request->query->get('scm'), ['svn', 'git']);
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $payload = $this->getPayload();

        $issues = $payload->getIssues();
        if (!$issues) {
            throw new InvalidArgumentException('No issues provided');
        }

        $ci = $payload->createCommit();
        $repo = $ci->getCommitRepo();

        if (!$repo->branchAllowed($payload->getBranch())) {
            throw new InvalidArgumentException("Branch not allowed: {$payload->getBranch()}");
        }

        $ci->setChangeset($payload->getCommitId());

        $em = Doctrine::getEntityManager();
        $cr = Doctrine::getCommitRepository();
        $ir = Doctrine::getIssueRepository();

        // XXX: take prj_id from first issue_id
        $issue = $ir->findById($issues[0]);
        $prj_id = $issue->getProjectId();

        $cr->preCommit($prj_id, $ci, $payload);

        // add commit files
        $cr->addCommitFiles($ci, $payload->getFiles());
        // add commits to issues
        $cr->addIssues($ci, $issues);

        $em->persist($ci);
        $em->flush();
    }

    /*
     * Get Hook Payload
     */
    private function getPayload()
    {
        $data = json_decode($this->request->getContent(), true);

        return new StandardPayload($data);
    }
}
