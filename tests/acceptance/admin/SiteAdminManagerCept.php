<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Login as regular user');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->dontSee('Einstellungen', '#adminLink');

$I->wantTo('Logout again');
$I->logout();


$I->wantTo('Login in as an admin');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->see(mb_strtoupper('Administrator*innen der Reihe'), 'h2');


$I->wantTo('Add testuser as admin');
$I->executeJS('$("input[name=addType]").val("email");');
$I->fillField('#addUsername', 'testuser@example.org');
$I->submitForm('#adminAddForm', [], 'addAdmin');
$I->see('testuser@example.org');

$I->seeElement('.admin2 .typeCon');
$I->seeElement('.admin2 .typeProposal');
$I->checkOption('.admin2 .typeSite input');
$I->dontSeeElement('.admin2 .typeCon');
$I->dontSeeElement('.admin2 .typeProposal');

$I->submitForm('#adminForm', [], 'saveAdmin');

$I->wantTo('Login in as testuser');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('Einstellungen', '#adminLink');


$I->wantTo('Go to admin administration');
$I->gotoStdAdminPage()->gotoSiteAccessPage();


$I->executeJS('$("input[name=addType]").val("wurzelwerk");');
$I->fillField('#addUsername', 'HoesslTo');
$I->submitForm('#adminAddForm', [], 'addAdmin');
$I->see('HoesslTo');


$I->wantTo('Remove testadmin as admin');
$I->see('testadmin@example.org');
$I->executeJS('$(".admin1 .removeAdmin").trigger("click");');
$I->seeBootboxDialog('Admin-Rechte entziehen');
$I->acceptBootboxConfirm();
$I->dontSee('testadmin@example.org');


$I->wantTo('Login in as testadmin');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontsee('Einstellungen', '#adminLink');
