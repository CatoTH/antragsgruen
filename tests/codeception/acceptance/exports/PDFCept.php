<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the pdf-compilation');
$I->gotoStdConsultationHome();
$I->click('#sidebar .pdfs');
// @TODO



$I->wantTo('test the amendment pdf-compilation');
$I->gotoStdConsultationHome();
$I->click('#sidebar .amendmentPdfs');

// @TODO

$I->see('Fail: Test case not implemented yet');
