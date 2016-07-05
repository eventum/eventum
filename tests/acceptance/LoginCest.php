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

class LoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function loginInvalidUser(AcceptanceTester $I, \Page\Login $loginPage)
    {
        $I->am('User'); // actor's role
        $I->wantTo('Login Failure'); // feature to test
        $I->amGoingTo('Login to website'); // feature to test

        $loginPage->login('admin@example.com', 'qwerty');
        $I->see('Error: The login / password combination could not be found in the system.');
    }

    public function loginAdminUser(AcceptanceTester $I, \Page\Login $loginPage)
    {
        $I->am('Admin'); // actor's role
        $I->wantTo('Login Success'); // feature to test
        $I->amGoingTo('Login to website'); // feature to test

        $loginPage->login('admin@example.com', 'admin');
        // after login should see "Create Issue"
        $I->see('Create Issue');
    }

    public function _after(AcceptanceTester $I)
    {
    }
}
