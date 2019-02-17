<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$username = 'testaccount@example.org';
$password = 'testpassword';

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

$I->fillField(['id' => 'username'], $username);
$I->fillField(['id' => 'name'], 'Tester');
$I->fillField(['id' => 'passwordInput'], $password);
$I->fillField(['id' => 'passwordConfirm'], $password);
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see(mb_strtoupper('Zugang bestätigen'), 'h1');

$I->fillField(['id' => 'code'], 'testCode');
$I->submitForm('#confirmAccountForm', []);
$I->see(mb_strtoupper('Zugang bestätigt'), 'h1');
$I->seeElement('.confirmedScreeningMsg');
$I->see('E-Mail sent to: testadmin@example.org'); // UserAsksPermission
$I->gotoConsultationHome(false);
$I->seeElement('.noAccessAlert');
$I->dontSeeElement('.askPermissionForm');
$I->seeElement('.askedForPermissionAlert');
$I->logout();


$I->wantTo('not grant access as an admin');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->dontSee('testaccount@example.org', '#accountsEditForm');
$I->see('testaccount@example.org', '#accountsScreenForm');
$I->checkOption('#screenUser' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->submitForm('#accountsScreenForm', [], 'noAccess');
$I->dontSee('testaccount@example.org', '#accountsEditForm');
$I->dontSee('testaccount@example.org', '#accountsScreenForm');
$I->gotoConsultationHome();
$I->logout();


$I->wantTo('ask again as user');
$I->click('#loginLink');
$I->fillField('#username', $username);
$I->fillField('#passwordInput', $password);
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->seeElement('.noAccessAlert');
$I->seeElement('.askPermissionForm');
$I->dontSeeElement('.askedForPermissionAlert');
$I->submitForm('.askPermissionForm', [], 'askPermission');
$I->see('E-Mail sent to: testadmin@example.org'); // UserAsksPermission
$I->seeElement('.noAccessAlert');
$I->dontSeeElement('.askPermissionForm');
$I->seeElement('.askedForPermissionAlert');
$I->logout();


$I->wantTo('grant access this time');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->dontSee('testaccount@example.org', '#accountsEditForm');
$I->see('testaccount@example.org', '#accountsScreenForm');
$I->checkOption('#screenUser' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->submitForm('#accountsScreenForm', [], 'grantAccess');
$I->see('testaccount@example.org', '#accountsEditForm');
$I->dontSee('testaccount@example.org', '#accountsScreenForm');
$I->gotoConsultationHome();
$I->logout();


$I->wantTo('be able to see everything now');

$I->click('#loginLink');
$I->fillField('#username', $username);
$I->fillField('#passwordInput', $password);
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->dontSeeElement('.noAccessAlert');
$I->dontSeeElement('.askPermissionForm');
$I->dontSeeElement('.askedForPermissionAlert');
$I->seeElement('.createMotion');
$I->seeElement('.motionLink2');
