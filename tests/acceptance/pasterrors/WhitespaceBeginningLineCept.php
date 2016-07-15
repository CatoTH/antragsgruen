<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('create an amendment without changes');
$I->gotoConsultationHome();
$I->loginAsStdUser();

$I->gotoMotion(true, 114);
$I->see('Leerzeichen-Test');
$I->click('#sidebar .amendmentCreate a');
$I->see('Änderungsantrag stellen', '.breadcrumb');

$I->wait(1);
$I->fillField('#initiatorPrimaryName', 'Mein Name');
$I->fillField('#initiatorEmail', 'test@example.org');

$I->submitForm('#amendmentEditForm', [], 'save');

$I->see(mb_strtoupper('Änderungsantrag bestätigen'), 'h1');
$I->see('Antragsteller*innen');
$I->dontSee('Von Zeile');
