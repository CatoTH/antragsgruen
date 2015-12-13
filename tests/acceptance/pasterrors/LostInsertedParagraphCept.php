<?php

/** @var \Codeception\Scenario $scenario */

$scenario->incomplete('"Zeilenumbruch" noch nicht implementiert');
$I->see('dummy');

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoAmendment(true, 2, 276);
$I->see('Zeilenumbruch');
