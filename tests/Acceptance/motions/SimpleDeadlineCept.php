<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->see('Antrag stellen');

$I->wantTo('set the deadline to the past');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->fillField('#typeSimpleDeadlineMotions', date('d.m.Y 00:00:00', time() - 10));
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome();
$I->dontSee('Antrag stellen');


$I->wantTo('access the page as admin');
$I->gotoMotionList();
$I->click('#newMotionBtn');
$I->click('.createMotion1');
$I->see('Antrag stellen', 'h1');


$I->wantTo('access the page as normal user');
$I->logout();
$I->see('Keine Berechtigung zum Anlegen von Anträgen.', '.alert-danger');


$I->wantTo('set the deadline to the future');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();

$I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->fillField('#typeSimpleDeadlineMotions', date('d.m.Y 00:00:00', time() + 3600 * 24));
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome();
$I->see('Antrag stellen');
