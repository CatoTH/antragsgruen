<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check the basic configuration');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.managedUserAccounts input');
$page->saveForm();

$I->gotoStdAdminPage()->gotoUserAdministration();

$I->wantTo('Create a dummy user with proposed procedure permissions');
$I->fillField('.addSingleInit .inputEmail', 'blibla@example.org');
$I->clickJS('.addUsersOpener.singleuser');
$I->wait(0.5);
$I->dontSeeElement('.addUsersByLogin.singleuser .showIfExists');
$I->seeElement('.addUsersByLogin.singleuser .showIfNew');
$I->fillField('#addSingleNameGiven', 'Bli');
$I->fillField('#addSingleNameFamily', 'Bla');
$I->fillField('#addSingleOrganization', 'Blubb Ltd.');
$I->dontSeeElement('#addUserPassword');
$I->clickJS('#addSingleGeneratePassword');
$I->seeElement('#addUserPassword');
$I->fillField('#addUserPassword', 'mypassword');
$I->checkOption('.addUsersByLogin.singleuser .userGroup3');
$I->uncheckOption('.addUsersByLogin.singleuser .userGroup4');
$I->seeCheckboxIsChecked('#addSingleSendEmail');
$I->submitForm('.addUsersByLogin.singleuser', [], 'addUsers');

$I->wait(0.2);
$I->see('Bli Bla', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->see('blibla@example.org, Blubb Ltd.', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->see('Antragskommission', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);


$I->wantTo('log in with the new user');
$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->fillField('#username', 'blibla@example.org');
$I->fillField('#passwordInput', 'mypassword');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Willkommen!', '.alert-success');
$I->seeElement('#motionListLink');
$I->click('#motionListLink');
$I->seeElement('.motionListForm');
