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
$I->gotoConsultationHome();

$I->loginAsStdAdmin();
$I->see('ADMIN', '#adminLink');

$I->wantTo('Go to admin administration');
$I->click('#adminLink');
$I->click('#adminsManageLink');
$I->see(mb_strtoupper('Administratoren der Reihe'), 'h1');


$I->wantTo('Add testuser as admin');
$I->fillField('#add_username', 'testuser@example.org');
$I->submitForm('#adminManageAddForm', [], 'adduser');
$I->see('testuser@example.org');

$I->wantTo('Login in as testuser');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->see('ADMIN', '#adminLink');


$I->wantTo('Go to admin administration');
$I->click('#adminLink');
$I->click('#adminsManageLink');
$I->see(mb_strtoupper('Administratoren der Reihe'), 'h1');



$I->wantTo('Remove testadmin as admin');
$I->see('testadmin@example.org');
$I->click('#removeAdmin1');
$I->dontSee('testadmin@example.org');


$I->wantTo('Login in as testadmin');
$I->logout();
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->dontsee('ADMIN', '#adminLink');
