<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate managed accounts');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('.forceLogin input');
$I->checkOption('.managedUserAccounts input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');
$I->logout();


$I->gotoConsultationHome(false);
$I->wait(0.5);
$I->dontSeeElement('.managedAccountHint');
$I->checkOption('#createAccount');
$I->seeElement('.managedAccountHint');
