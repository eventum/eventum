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

namespace Eventum\Model\Repository;

use Doctrine\ORM\EntityRepository;
use Eventum\Model\Entity\SearchProfile;

class SearchProfileRepository extends EntityRepository
{
    public function getIssueProfile(int $usr_id, int $prj_id): ?SearchProfile
    {
        return $this->getProfileByType($usr_id, $prj_id, 'issue');
    }

    public function getProfileByType(int $usr_id, int $prj_id, string $type): ?SearchProfile
    {
        return $this->findOneBy(['type' => $type, 'userId' => $usr_id, 'projectId' => $prj_id]);
    }

    public function remove(SearchProfile $profile): void
    {
        $this->_em->remove($profile);
        $this->_em->flush();
    }
}
