<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->gotoConsultationHome();

$I->wantTo('be able to restrict password changing as super-admin');
$I->loginAsGlobalAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user1 .btnEdit');
$I->wait(0.5);
$I->dontSeeCheckboxIsChecked('.preventPwdChangeHolder input');
$I->clickJS('.preventPwdChangeHolder input');
$I->seeCheckboxIsChecked('.preventPwdChangeHolder input');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);


$I->wantTo('not see the password option anymore');
$I->logout();
$I->loginAsStdAdmin();
$I->click('#myAccountLink');
$I->dontSeeElement('#userPwd');
$I->dontSeeElement('#userPwd2');
$I->seeElement('#nameFamily');

$I->wantTo('allow it again');
$I->logout();
$I->loginAsGlobalAdmin();
$I->gotoStdAdminPage()->gotoUserAdministration();
$I->clickJS('.user1 .btnEdit');
$I->wait(0.5);
$I->seeCheckboxIsChecked('.preventPwdChangeHolder input');
$I->clickJS('.preventPwdChangeHolder input');
$I->dontSeeCheckboxIsChecked('.preventPwdChangeHolder input');
$I->clickJS('.editUserModal .btnSave');
$I->wait(0.3);


$I->wantTo('see the password option again');
$I->logout();
$I->loginAsStdAdmin();
$I->click('#myAccountLink');
$I->seeElement('#userPwd');
$I->seeElement('#userPwd2');
$I->seeElement('#nameFamily');
