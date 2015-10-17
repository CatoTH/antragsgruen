<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoAmendment(true, 2, 276);
$I->see('Leerzeichen');
$I->see('Zeilenumbruch');
