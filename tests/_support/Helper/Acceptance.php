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

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Delete setup config.
     * This is needed to happen before Page\Setup is loaded as it requests the "/setup/" url in its constructor
     *
     * @param \AcceptanceTester $I
     */
    public function deleteSetupConfig(\AcceptanceTester $I)
    {
        $I->deleteFile('dist/eventum/config/config.php');
    }
}
