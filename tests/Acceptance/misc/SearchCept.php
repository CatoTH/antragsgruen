<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
/*
 * @TODO Trying to search without an search term
$I->wantTo('search without enterig a term');
$I->fillField('#sidebar .query', '');
$I->submitForm('#sidebar .form-search', [], '');
$I->see('Kein Suchbegriff eingegeben');
$I->see(mb_strtoupper('Test2'), 'h1');
*/


$I->wantTo('search a motion');

$I->fillField('#sidebar .query', 'O’zapft');
$I->submitForm('#sidebar .form-search', [], '');

$I->see('A2: O’zapft');
$I->dontSee('A3: Test');


$I->wantTo('check that the backlinks in the motions work');
$I->click('.motion2 a');
$I->see('Suche', '.breadcrumb');



$I->wantTo('search an amendment');
$I->gotoConsultationHome();
$I->fillField('#sidebar .query', 'neuer absatz');
$I->submitForm('#sidebar .form-search', [], '');

$I->see('Ä2 zu A2');

$I->wantTo('check that the backlinks in the amendments work');
$I->click('.amendment3 a');
$I->see('Suche', '.breadcrumb');
