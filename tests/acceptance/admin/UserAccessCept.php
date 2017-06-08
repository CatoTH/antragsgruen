<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('enforce login on this site');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();

$I->checkOption('input[name=forceLogin]');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');
$I->see('Gespeichert.', '#siteSettingsForm');

$I->logout();
$I->gotoConsultationHome(false);
$I->see('Login', 'h1');

$I->loginAsStdUser();
$I->dontSee('Kein Zugriff', 'h1');
$I->see('Test2', 'h1');

$I->logout();



$I->wantTo('restrict login');

$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoSiteAccessPage();

$I->dontSeeElement('#accountsCreateForm');
$I->checkOption('input[name=managedUserAccounts]');
$I->wait(1);
$I->seeElement('#accountsCreateForm');


$I->submitForm('#siteSettingsForm', [], 'saveLogin');
$I->see('Gespeichert.', '#siteSettingsForm');

$I->logout();
$I->gotoConsultationHome(false);
$I->see('Login', 'h1');

$I->loginAsStdUser();
$I->see('Kein Zugriff', 'h1');
$I->dontSee('Test2', 'h1');

$I->logout();



$I->wantTo('add my test user to the list (but make a mistake)');

$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoSiteAccessPage();

$I->seeElement('#accountsCreateForm');

$I->fillField('#emailAddresses', "testuser@example.org\ntestuser2@example.org");
$I->fillField('#names', "Test user");

$I->submitForm('#accountsCreateForm', [], 'addUsers');
$I->seeBootboxDialog('exakt ein Name');
$I->acceptBootboxAlert();

$I->fillField('#names', "Test user\nTest User 2");
$I->fillField('#emailText', 'abc %LINK%');
$I->submitForm('#accountsCreateForm', [], 'addUsers');
$I->seeBootboxDialog('%ACCOUNT%');
$I->acceptBootboxAlert();



$I->wantTo('add my test users to the list');

$I->fillField('#emailText', '%LINK% / %ACCOUNT%');
$I->submitForm('#accountsCreateForm', [], 'addUsers');
$I->see('2 Benutzer*innen wurden eingetragen.', '.showManagedUsers');
$I->seeElement('#accountsCreateForm');


$I->wantTo('add one another time');

$I->fillField('#emailAddresses', "testuser2@example.org");
$I->fillField('#names', "Test user");
$I->submitForm('#accountsCreateForm', [], 'addUsers');

$I->see('Folgende Benutzer*innen hatten bereits Zugriff: testuser2@example.org', '.showManagedUsers');
$I->see('testuser@example.org', '.accountListTable');

$I->logout();


$I->wantTo('log in as one of the users');
$I->gotoConsultationHome(false);
$I->loginAsStdUser();
$I->gotoConsultationHome(true);
$I->seeElement('#sidebar .createMotion');

$I->logout();



$I->wantTo('restrict creating motions for registered users');

$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoSiteAccessPage();
$I->dontSeeElement('#policyRestrictForm');
$I->uncheckOption('#siteSettingsForm .forceLogin input');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');
$I->seeElement('#policyRestrictForm');
$I->submitForm('#policyRestrictForm', [], 'policyRestrictToUsers');
$I->dontSeeElement('#policyRestrictForm');

$I->uncheckOption('.accountListTable .user2 .accessCreateCol input');
$I->submitForm('#accountsEditForm', [], 'saveUsers');
$I->dontSeeCheckboxIsChecked('.accountListTable .user2 .accessCreateCol input');
$I->seeCheckboxIsChecked('.accountListTable .user2 .accessViewCol input');

$I->logout();


$I->wantTo('try to create a motion as an user');

$I->gotoConsultationHome();
$I->seeElement('#sidebar .createMotion');
$I->click('#sidebar .createMotion');
$I->see('Login', 'h1');

$I->loginAsStdUser();

$I->dontSeeElement('#sidebar .createMotion');

$I->logout();


$I->wantTo('grant her write access');

$I->loginAsStdAdmin();
$I->gotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('.accountListTable .user2 .accessCreateCol input');
$I->submitForm('#accountsEditForm', [], 'saveUsers');

$I->logout();

$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->seeElement('#sidebar .createMotion');
$I->click('#sidebar .createMotion');
$I->see('Antrag stellen', 'h1');
