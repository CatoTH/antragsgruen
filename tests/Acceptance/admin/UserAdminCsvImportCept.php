<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Test CSV User Import JS Frontend Logic');
$I->loginAndGotoStdAdminPage()->gotoUserAdministration();

// Ensure the form is accessible via the opener
$I->clickJS('.addUsersOpener.csv');

// Form and elements should be visible
$I->seeElement('#csvImportForm');
$I->seeElement('#csvSubmitBtn');

// Progress container should be hidden initially
$I->dontSeeElement('#csvProgressContainer:not(.hidden)');

// Remove the required attribute so we can submit the form without an actual file, 
// triggering the JS submit event instead of HTML5 validation
$I->executeJS("document.querySelector('input[name=\"csvFile\"]').removeAttribute('required');");

// Click the submit button
$I->clickJS('#csvSubmitBtn');

// The JS logic should catch the submit, prevent default, and show the progress container
$I->wait(0.5);
$I->seeElement('#csvProgressContainer:not(.hidden)');
