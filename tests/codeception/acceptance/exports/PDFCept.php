<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();



$I->wantTo('disable PDFs');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#pdfLayout', -1);
$motionTypePage->saveForm();

$I->gotoStdConsultationHome();
$I->dontSee('PDF');
$I->dontSeeElement('#sidebar .pdfs');
$I->click('.motionLink2');
$I->dontSee('PDF');



$I->wantTo('activate PDFs again');
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#pdfLayout', 0);
$motionTypePage->saveForm();

$I->gotoStdConsultationHome();
$I->see('PDF');
$I->seeElement('#sidebar .pdfs');
$I->click('.motionLink2');
$I->see('PDF');


$I->wantTo('test the pdf-compilation');
$I->gotoStdConsultationHome();
$I->click('#sidebar .pdfs');
// @TODO



$I->wantTo('test the amendment pdf-compilation');
$I->gotoStdConsultationHome();
$I->click('#sidebar .amendmentPdfs');

// @TODO

$I->fail('Test case not implemented yet');
