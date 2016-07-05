<?php

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
