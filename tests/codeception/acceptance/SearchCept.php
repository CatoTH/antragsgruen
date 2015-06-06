<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('search a motion');

$I->fillField('#sidebar .query', 'O’zapft');
$I->submitForm('#sidebar .form-search', [], '');

$I->see('A2: O’zapft');
$I->dontSee('A3: Test');


// @TODO: Search amendments
