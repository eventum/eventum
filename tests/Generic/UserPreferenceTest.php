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

namespace Eventum\Test\Generic;

use Doctrine\ORM\EntityManager;
use Eventum\Db\Doctrine;
use Eventum\Model\Repository\UserPreferenceRepository;
use Eventum\ServiceContainer;
use Eventum\Test\TestCase;
use ProjectSeeder;

/**
 * @group db
 */
class UserPreferenceTest extends TestCase
{
    /** @var EntityManager */
    private $em;
    /** @var UserPreferenceRepository */
    private $repo;

    public function setUp(): void
    {
        $this->em = ServiceContainer::getEntityManager();
        $this->repo = Doctrine::getUserPreferenceRepository();
    }

    public function testModifyCascade(): void
    {
        $usr_id = 771718;
        $this->deletePreferences($usr_id);

        $projects = [
            ProjectSeeder::DEFAULT_PROJECT_ID => [
                'receive_new_issue_email' => true,
                'receive_assigned_email' => false,
                'receive_copy_of_own_action' => false,
            ],
        ];
        $this->repo->updateProjectPreference($usr_id, $projects);
    }

    private function deletePreferences(int $usr_id): void
    {
        if ($userPrefs = $this->repo->find($usr_id)) {
            // this cascades delete user_preference and user_project_preference
            $this->em->remove($userPrefs);
            $this->em->flush();
        }

        $this->assertNull($this->repo->find($usr_id));
    }
}
