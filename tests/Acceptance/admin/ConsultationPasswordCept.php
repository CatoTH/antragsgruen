<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create a second consultation');
$I->gotoConsultationHome(false);
$I->loginAndGotoStdAdminPage();
$I->click('.siteConsultationsLink');
$I->fillField('#newTitle', 'Test3');
$I->fillField('#newShort', 'test3');
$I->fillField('#newPath', 'test3');
$I->submitForm('.consultationCreateForm', [], 'createConsultation');


$I->wantTo('set a password for the first consultation');
$page = $I->gotoStdAdminPage('stdparteitag', 'std-parteitag')->gotoConsultation();
$I->dontSeeCheckboxIsChecked("//input[@name='pwdProtected']");
$I->dontSeeElement('.setPasswordHolder');
$I->executeJS('$("input[name=\'pwdProtected\']").click();');
$I->wait(0.5);
$I->seeElement('.setPasswordHolder');
$I->fillField("//input[@name='consultationPassword']", "stdParteitagPwd");
$page->saveForm();
$I->seeCheckboxIsChecked("//input[@name='pwdProtected']");
$I->seeElement('.setNewPassword');


$I->wantTo('confirm that both consultations have a password set');
$I->logout();
$I->gotoConsultationHome(false, 'stdparteitag', 'test3');
$I->see('Login', 'h1');
$I->seeElement('.loginConPwd');

$I->gotoConsultationHome(false, 'stdparteitag', 'std-parteitag');
$I->see('Login', 'h1');
$I->seeElement('#conPwdForm');

$I->fillField('#conpwd', 'stdParteitagWrong');
$I->submitForm('#conPwdForm', [], 'loginconpwd');
$I->seeElement('#conPwdForm .alert-danger');
$I->fillField('#conpwd', 'stdParteitagPwd');
$I->submitForm('#conPwdForm', [], 'loginconpwd');
$I->see('Test2', 'h1');
$I->seeCookie('consultationPwd');


$I->wantTo('change the password for one consultation');
$I->resetCookie('consultationPwd');
$I->gotoConsultationHome(false, 'stdparteitag', 'test3');
$I->see('Login', 'h1');

// The login form is hidden by default
$I->dontSeeElement('.loginUsername');
$I->seeElement('.usernameLoginOpener');
$I->clickJS('.usernameLoginOpener button');
$I->seeElement('.loginUsername');
$I->fillField('#username', 'testadmin@example.org');
$I->fillField('#passwordInput', 'testadmin');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');

$page = $I->gotoStdAdminPage('stdparteitag', 'test3')->gotoConsultation();
$I->seeElement('.setNewPassword');
$I->clickJS('.setNewPassword');
$I->wait(0.5);
$I->seeElement('.setPasswordHolder');
$I->seeCheckboxIsChecked("//input[@name='otherConsultations'][@value='1']");
$I->fillField("//input[@name='consultationPassword']", "Test3Pwd");
$I->checkOption("//input[@name='otherConsultations'][@value='0']");
$page->saveForm();
$I->logout();


$I->wantTo('confirm both passwords work');
$I->gotoConsultationHome(false, 'stdparteitag', 'test3');
$I->see('Login', 'h1');
$I->seeElement('.loginConPwd');
$I->fillField('#conpwd', 'Test3Pwd');
$I->submitForm('#conPwdForm', [], 'loginconpwd');
$I->see('Test3', 'h1');

$I->gotoConsultationHome(false, 'stdparteitag', 'std-parteitag');
$I->see('Login', 'h1');
$I->seeElement('.loginConPwd');
$I->fillField('#conpwd', 'stdParteitagPwd');
$I->submitForm('#conPwdForm', [], 'loginconpwd');
$I->see('Test2', 'h1');

$I->gotoConsultationHome(false, 'stdparteitag', 'test3');
$I->see('Test3', 'h1');
