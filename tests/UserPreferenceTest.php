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

namespace Eventum\Test;

use Doctrine\ORM\EntityManager;
use Eventum\Db\Doctrine;
use Eventum\Model\Repository\UserPreferenceRepository;
use Prefs;

/**
 * @group db
 */
class UserPreferenceTest extends TestCase
{
    /** @var EntityManager */
    private $em;
    /** @var UserPreferenceRepository */
    private $repo;

    public function setUp()
    {
        $this->em = Doctrine::getEntityManager();
        $this->repo = Doctrine::getUserPreferenceRepository();
    }

    public function testCompatibility(): void
    {
        $usr_id = APP_SYSTEM_USER_ID;

        $userPrefs = $this->repo->findById($usr_id);
        $prefs = Prefs::get($usr_id);

        $this->assertEquals($prefs['timezone'], $userPrefs->getTimezone());
        $this->assertEquals($prefs['week_firstday'], $userPrefs->getWeekFirstday());
        $this->assertEquals($prefs['list_refresh_rate'], $userPrefs->getListRefreshRate());
        $this->assertEquals($prefs['email_refresh_rate'], $userPrefs->getEmailRefreshRate());
        $this->assertEquals($prefs['email_signature'], $userPrefs->getEmailSignature());
        $this->assertEquals($prefs['close_popup_windows'], $userPrefs->autoClosePopupWindow());
        $this->assertEquals($prefs['relative_date'], $userPrefs->useRelativeDate());
        $this->assertEquals($prefs['collapsed_emails'], $userPrefs->collapsedEmails());

        // booleans
        $this->assertEquals($prefs['auto_append_email_sig'], (int)$userPrefs->autoAppendEmailSignature());
        $this->assertEquals($prefs['auto_append_note_sig'], (int)$userPrefs->autoAppendNoteSignature());
        $this->assertEquals($prefs['markdown'], (int)$userPrefs->isMarkdownEnabled());

        foreach ($userPrefs->getProjects() as $projectPrefs) {
            $prj_id = $projectPrefs->getProjectId();
            // all fields are booleans
            $this->assertEquals($prefs['receive_assigned_email'][$prj_id], (int)$projectPrefs->receiveAssignedEmail());
            $this->assertEquals($prefs['receive_new_issue_email'][$prj_id], (int)$projectPrefs->receiveNewIssueEmail());
            $this->assertEquals($prefs['receive_copy_of_own_action'][$prj_id], (int)$projectPrefs->receiveCopyOfOwnAction());
        }
    }

    public function testModifyCascade(): void
    {
        $usr_id = 771718;
        $this->deletePreferences($usr_id);

        $projects = [
            1 => [
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
