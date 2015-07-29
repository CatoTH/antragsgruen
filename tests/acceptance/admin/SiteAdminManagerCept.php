<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Login as regular user');
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->dontSee('ADMIN', '#adminLink');

$I->wantTo('Logout again');
$I->logout();


$I->wantTo('Login in as an admin');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->see(mb_strtoupper('Administrator_Innen der Reihe'), 'h2');


$I->wantTo('Add testuser as admin');
$I->fillField('#add_username', 'testuser@example.org');
$I->submitForm('#adminForm', [], 'addAdmin');
$I->see('testuser@example.org');

$I->wantTo('Login in as testuser');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('ADMIN', '#adminLink');


$I->wantTo('Go to admin administration');
$I->gotoStdAdminPage()->gotoSiteAccessPage();


$I->wantTo('Remove testadmin as admin');
$I->see('testadmin@example.org');
$I->click('.admin1 .removeAdmin');
$I->wait(1);
$I->see('Admin-Rechte entziehen');
$I->acceptBootboxConfirm();
$I->dontSee('testadmin@example.org');


$I->wantTo('Login in as testadmin');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontsee('ADMIN', '#adminLink');
