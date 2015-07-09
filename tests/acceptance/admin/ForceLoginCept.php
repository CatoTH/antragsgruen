<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome(false);
$I->see('Test2', 'h1');

$I->wantTo('enforce login');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('input[name=forceLogin]');
$I->submitForm('#siteSettingsForm', [], 'save');
$I->logout();

$I->gotoConsultationHome(false);
$I->see('Login', 'h1');

$I->wantTo('log in');
$I->loginAsStdUser();
$I->see('Test2', 'h1');


$I->wantTo('disable it again');
$I->logout();
$I->gotoConsultationHome(false);
$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoSiteAccessPage();
$I->uncheckOption('input[name=forceLogin]');
$I->submitForm('#siteSettingsForm', [], 'save');
$I->logout();

$I->gotoConsultationHome(false);
$I->see('Test2', 'h1');
