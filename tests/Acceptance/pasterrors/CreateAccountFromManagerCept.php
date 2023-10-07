<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\ManagerStartPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create an account from the manager page');
$I->openPage(ManagerStartPage::class);
$I->click('#loginLink');
$I->dontSeeElement('#name');
$I->click('#createAccount');
$I->seeElement('#name');

$I->fillField('#username', 'newuser@example.org');
$I->fillField('#passwordInput', 'newuser2');
$I->fillField('#passwordConfirm', 'newuser2');
$I->fillField('#name', 'New User');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');
