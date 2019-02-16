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


$I->wantTo('create an account');
$I->gotoConsultationHome(false);
$I->wait(0.5);
$I->dontSeeElement('.managedAccountHint');
$I->checkOption('#createAccount');
$I->seeElement('.managedAccountHint');

$I->fillField(['id' => 'username'], 'testaccount@example.org');
$I->fillField(['id' => 'name'], 'Tester');
$I->fillField(['id' => 'passwordInput'], 'testpassword');
$I->fillField(['id' => 'passwordConfirm'], 'testpassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');

$I->fillField(['id' => 'code'], 'testCode');
$I->submitForm('#confirmAccountForm', []);
$I->see(mb_strtoupper('Zugang bestätigt'), 'h1');
$I->seeElement('.confirmedScreeningMsg');
$I->see('E-Mail sent to: testadmin@example.org'); // UserAsksPermission
$I->logout();


$I->wantTo('not grant access as an admin');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->dontSee('testaccount@example.org', '#accountsEditForm');
$I->see('testaccount@example.org', '#accountsScreenForm');
$I->checkOption('#screenUser9');
$I->submitForm('#accountsScreenForm', [], 'noAccess');
$I->dontSee('testaccount@example.org', '#accountsEditForm');
$I->dontSee('testaccount@example.org', '#accountsScreenForm');
$I->gotoConsultationHome();
$I->logout();


$I->wantTo('ask again as user');
$I->loginAsStdUser();
