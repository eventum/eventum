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
use Eventum\Model\Entity;
use Eventum\Scm\Payload\StandardPayload;
use InvalidArgumentException;
use Issue;
use Symfony\Component\HttpFoundation\Request;

class Cvs extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function can()
    {
        // must be POST
        if ($this->request->getMethod() != Request::METHOD_POST) {
            return false;
        }
        // require 'scm=cvs' GET parameter
        return $this->request->query->get('scm') === 'cvs';
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

        $em = Doctrine::getEntityManager();
        $cr = Doctrine::getCommitRepository();

        $commitId = $payload->getCommitId();
        $ci = $cr->findOneByChangeset($commitId);

        // if ci already seen, skip adding commit and issue association
        // but still process commit files.
        // as cvs handler sends files in subdirs as separate requests
        if (!$ci) {
            $ci = $payload->createCommit();
            // set this last, as it may need other $ci properties
            $ci->setChangeset($commitId ?: $this->generateCommitId($ci));

            $repo = $ci->getCommitRepo();
            if (!$repo->branchAllowed($ci->getBranch())) {
                throw new \InvalidArgumentException("Branch not allowed: {$ci->getBranch()}");
            }

            $ir = Doctrine::getIssueRepository();

            // XXX: take prj_id from first issue_id
            $issue = $ir->findById($issues[0]);
            $prj_id = $issue->getProjectId();

            $cr->preCommit($prj_id, $ci, $payload);
            $em->persist($ci);

            // add commit files
            $cr->addCommitFiles($ci, $payload->getFiles());

            // add commits to issues
            $cr->addIssues($ci, $issues);
        } else {
            // add more files to existing commit
            // NOTE: workflow/eventdispatcher is not fired
            $cr->addCommitFiles($ci, $payload->getFiles());
        }

        $em->flush();
    }

    /**
     * Seconds to allow commit date to differ to consider them as same commit id
     */
    const COMMIT_TIME_DRIFT = 10;

    /**
     * Generate commit id
     *
     * @param Entity\Commit $ci
     * @return string
     */
    private function generateCommitId(Entity\Commit $ci)
    {
        $seed = [
            $ci->getCommitDate()->getTimestamp() / self::COMMIT_TIME_DRIFT,
            $ci->getAuthorName(),
            $ci->getAuthorEmail(),
            $ci->getMessage(),
        ];
        $checksum = md5(implode('', $seed));

        // CVS commitid is 16 byte length base62 encoded random and seems always end with z0
        // so we use 14 bytes from md5, and z1 suffix to get similar (but not conflicting) commitid
        return substr($checksum, 1, 14) . 'z1';
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
