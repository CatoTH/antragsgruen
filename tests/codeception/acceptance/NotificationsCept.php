<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->gotoStdConsultationHome();
$I->click('#sidebar .notifications');

$I->see(mb_strtoupper('Benachrichtigungen'), 'h1');

$I->fail('Test not implemented yet');
// @TODO Not implemented yet
