<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(3);
$I->seeElement('.amendmentCreate');

$I->wantTo('set the deadline to the past');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->fillField('#typeSimpleDeadlineAmendments', date('d.m.Y 00:00:00', time() - 10));
$I->submitForm('.adminTypeForm', [], 'save');


$I->wantTo('still see it as an admin');
$I->gotoConsultationHome()->gotoMotionView(3);
$I->seeElement('.amendmentCreate a');
$I->click('.amendmentCreate a');
$I->see('Änderungsantrag zu A3: Textformatierungen stellen', 'h1');

$I->wantTo('get an error as a normal user');
$I->logout();
$I->dontSee('Änderungsantrag anlegen', 'h1');
$I->see('Keine Berechtigung zum Anlegen von Änderungsanträgen.', '.alert-danger');
$I->gotoConsultationHome()->gotoMotionView(3);
$I->dontSeeElement('.amendmentCreate a');
$I->see('Der Antragsschluss ist vorbei', '.amendmentCreate');


$I->wantTo('set the deadline to the future');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->fillField('#typeSimpleDeadlineAmendments', date('d.m.Y 00:00:00', time() + 3600 * 24));
$I->submitForm('.adminTypeForm', [], 'save');

$I->gotoConsultationHome()->gotoMotionView(3);
$I->seeElement('.amendmentCreate a');
