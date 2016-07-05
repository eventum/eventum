<?php
/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->wantTo('Ensure that frontpage has Login Form');
$I->amOnPage('/');
$I->see('Login:');
$I->see('Password:');
