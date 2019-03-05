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

use Eventum\Db\Doctrine;
use Prefs;

/**
 * @group db
 */
class UserPreferenceTest extends TestCase
{
    public function testCompatibility(): void
    {
        $usr_id = APP_SYSTEM_USER_ID;

        $repo = Doctrine::getUserPreferenceRepository();
        $userPrefs = $repo->findById($usr_id);
        $prefs = Prefs::get($usr_id);

        $this->assertEquals($prefs['timezone'], $userPrefs->getTimezone());
        $this->assertEquals($prefs['week_firstday'], $userPrefs->getWeekFirstday());
        $this->assertEquals($prefs['list_refresh_rate'], $userPrefs->getListRefreshRate());
        $this->assertEquals($prefs['email_refresh_rate'], $userPrefs->getEmailRefreshRate());
        $this->assertEquals($prefs['email_signature'], $userPrefs->getEmailSignature());
        $this->assertEquals($prefs['close_popup_windows'], $userPrefs->autoClosePopupWindow());
        $this->assertEquals($prefs['relative_date'], $userPrefs->useRelativeDate());
        $this->assertEquals($prefs['collapsed_emails'], $userPrefs->collapseEmails());

        // booleans
        $this->assertEquals($prefs['auto_append_email_sig'], (int)$userPrefs->autoAppendEmailSignature());
        $this->assertEquals($prefs['auto_append_note_sig'], (int)$userPrefs->autoAppendNoteSignature());
        $this->assertEquals($prefs['markdown'], (int)$userPrefs->isMarkdownEnabled());
    }
}
