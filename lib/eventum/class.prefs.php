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

use Eventum\Db\Doctrine;
use Eventum\Model\Entity\UserPreference;
use Eventum\Model\Entity\UserProjectPreference;

/**
 * Class to handle the business logic related to the user preferences
 * available in the application.
 */
class Prefs
{
    public static function getUserPreference(int $usr_id): UserPreference
    {
        // NOTE: do not need to cache here,
        // Doctrine UnitOfWork does this pretty well even without having Cache configured
        $repo = Doctrine::getUserPreferenceRepository();

        return $repo->findOrCreate($usr_id);
    }

    public static function getProjectPreference(int $prj_id, int $usr_id): UserProjectPreference
    {
        return self::getUserPreference($usr_id)->findOrCreateProjectById($prj_id);
    }

    public static function getTimezone(int $usr_id): string
    {
        return self::getUserPreference($usr_id)->getTimezone();
    }

    /**
     * Method used to get the preferences set by a specific user.
     *
     * @param int $usr_id The user ID
     * @return array The preferences
     * @deprecated
     */
    public static function get($usr_id): array
    {
        $prefs = self::getUserPreference($usr_id);

        return [
            'auto_append_email_sig' => (int)$prefs->autoAppendNoteSignature(),
            'auto_append_note_sig' => (int)$prefs->autoAppendNoteSignature(),
            'close_popup_windows' => (int)$prefs->autoClosePopupWindow(),
            'email_signature' => $prefs->getEmailSignature() ?? '',
            'relative_date' => (int)$prefs->useRelativeDate(),
            'timezone' => $prefs->getTimezone(),
            'week_firstday' => $prefs->getWeekFirstday(),
        ];
    }
}
