<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->skip('Not implemented yet');

$I->gotoStdConsultationHome();
$I->click('#sidebar .notifications');

$I->see(mb_strtoupper('Benachrichtigungen'), 'h1');

