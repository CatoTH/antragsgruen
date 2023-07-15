<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoAmendment(true, '2', 1);
$I->see('Oamoi a Maß');


$I->gotoAmendment(true, '3', 2);
$I->see('Um das ganze mal zu testen');
$I->dontSee('###FORCELINEBREAK###');
