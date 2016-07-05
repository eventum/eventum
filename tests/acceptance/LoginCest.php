<?php

class LoginCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/');
    }

    public function loginInvalidUser(AcceptanceTester $I)
    {
        $I->am('User'); // actor's role
        $I->wantTo('Login'); // feature to test
        $I->amGoingTo('Login to website'); // feature to test

        $I->see('Login:');
        $I->see('Password:');

        $I->fillField('email', 'admin@example.com');
        $I->fillField('passwd', 'qwerty');
        $I->click('Login');
        $I->see('Error: The login / password combination could not be found in the system.');
    }

    public function loginAdminUser(AcceptanceTester $I)
    {
        $I->am('Admin'); // actor's role
        $I->wantTo('Login'); // feature to test
        $I->amGoingTo('Login to website'); // feature to test

        $I->see('Login:');
        $I->see('Password:');

        $I->fillField('email', 'admin@example.com');
        $I->fillField('passwd', 'admin');
        $I->click('Login');

        // after login should see "Create Issue"
        $I->see('Create Issue');
    }

    public function _after(AcceptanceTester $I)
    {
    }
}
