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

namespace Page;

/**
 * Page for Setup (Installation) actions
 */
class Setup
{
    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;

        $I->amOnPage('/setup/');
    }

    /**
     * Should not see login form
     * If we see login form, the application is already registered
     */
    public function dontSeeLogin()
    {
        $I = $this->tester;
        $I->dontSee('Eventum - Login', '//h1');
    }

    public function shouldSeeInstallForm()
    {
        $I = $this->tester;
        $I->see('Eventum Installation', '//h1');
    }

    /**
     * Submit installation form
     *
     * @param array $params
     * @return $this
     */
    public function setup($params)
    {
        $I = $this->tester;

        $I->submitForm('#install_form', $params);
        $I->see('Success!');

        $I->see("Email Address: {$params['admin_user']} (literally)");
        $I->see("Password: {$params['admin_password']}");

        return $this;
    }
}
