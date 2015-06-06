<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the motion feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedMotions');

$I->see('O’zapft is! PDF');
$I->see('Test');
// @TODO


$I->wantTo('test the amendment feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedAmendments');

$I->see('Tester');
$I->see('Ä1');
// @TODO



$I->wantTo('test the comment feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedComments');

// @TODO



$I->wantTo('test the overall feed');
$I->gotoStdConsultationHome();
$I->click('#sidebar .feedAll');

$I->see('O’zapft is! PDF');
$I->see('Test');
$I->see('Tester');
$I->see('Ä1');
// @TODO
