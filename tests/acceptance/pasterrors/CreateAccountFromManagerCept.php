<?php

/** @var \Codeception\Scenario $scenario */
use app\tests\_pages\ManagerStartPage;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create an account from the manager page');
ManagerStartPage::openBy($I);
$I->click('#loginLink');
$I->dontSeeElement('#name');
$I->click('#createAccount');
$I->seeElement('#name');

$I->fillField('#username', 'newuser@example.org');
$I->fillField('#passwordInput', 'newuser');
$I->fillField('#passwordConfirm', 'newuser');
$I->fillField('#name', 'New User');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');
