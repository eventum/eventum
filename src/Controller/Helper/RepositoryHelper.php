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

namespace Eventum\Controller\Helper;

use Eventum\Db\Doctrine;
use Eventum\Model\Entity\UserPreference;
use Eventum\Model\Repository\CustomFieldRepository;
use Eventum\Model\Repository\UserPreferenceRepository;

class RepositoryHelper
{
    public function getUserPreferences(int $usr_id): UserPreference
    {
        return $this->getUserPreferenceRepository()->findOrCreate($usr_id);
    }

    public function getUserPreferenceRepository(): UserPreferenceRepository
    {
        return Doctrine::getUserPreferenceRepository();
    }

    public function getCustomFieldRepository(): CustomFieldRepository
    {
        return Doctrine::getCustomFieldRepository();
    }
}
