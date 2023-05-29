<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsStdUser();
$I->click('#myAccountLink');
$I->click('.exportRow a');

$I->canSeeInPageSource('supported_motions');
$I->canSeeInPageSource('Testing_proposed_changes'); // Motion
$I->canSeeInPageSource('Und noch eine neue Zeile'); // Amendment
