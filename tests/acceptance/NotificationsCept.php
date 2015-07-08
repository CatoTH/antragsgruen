<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('Not implemented yet');

$I->gotoConsultationHome();
$I->click('#sidebar .notifications');

$I->see(mb_strtoupper('Benachrichtigungen'), 'h1');

