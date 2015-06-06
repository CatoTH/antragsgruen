<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the motion feed');
$I->gotoStdConsultationHome();

$I->click('#sidebar .feedMotions');

$I->seeInPageSource('O’zapft is!');
$I->seeInPageSource('Test');

// @TODO


$I->wantTo('test the amendment feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedAmendments');

$I->seeInPageSource('Tester');
$I->seeInPageSource('Ä1');
// @TODO



$I->wantTo('test the comment feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedComments');

// @TODO



$I->wantTo('test the overall feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedAll');

$I->seeInPageSource('O’zapft is! PDF');
$I->seeInPageSource('Test');
$I->seeInPageSource('Tester');
$I->seeInPageSource('Ä1');
// @TODO
