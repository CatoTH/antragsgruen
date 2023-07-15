<?php

use Tests\Support\AcceptanceTester;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$username = 'testaccount@example.org';
$password = 'testpassword';

$I->wantTo('activate managed accounts');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('.forceLogin input');
$I->checkOption('.managedUserAccounts input');
$page->saveForm();
$I->logout();


$I->wantTo('create an account');
$I->gotoConsultationHome(false);
$I->wait(0.2);
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


$I->wantTo('Add the user through the regular addition process');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();
$I->dontSee('testaccount@example.org', '.userAdminList');
$I->see('testaccount@example.org', '#accountsScreenForm');

$I->fillField('.addSingleInit .inputEmail', $username);
$I->clickJS('.addUsersOpener.singleuser');
$I->wait(0.5);
$I->seeElement('.addUsersByLogin.singleuser .showIfExists');
$I->dontSeeElement('.addUsersByLogin.singleuser .showIfNew');
$I->submitForm('.addUsersByLogin.singleuser', [], 'addUsers');

$I->wait(0.2);
$I->see('Tester', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->see('testaccount@example.org', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);
$I->see('Teilnehmer*in', '.user' . AcceptanceTester::FIRST_FREE_USER_ID);

$I->wantTo('not see them at the requester list anymore');
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
