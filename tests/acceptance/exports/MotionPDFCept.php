<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('disable PDFs');
$I->gotoStdConsultationHome();


$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#pdfLayout', -1);
$motionTypePage->saveForm();

$I->gotoStdConsultationHome();
$I->dontSee('PDF');
$I->dontSeeElement('#sidebar .motionPdfCompilation');
$I->click('.motionLink2');
$I->dontSee('PDF');



$I->wantTo('activate PDFs again');
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->selectOption('#pdfLayout', 0);
$motionTypePage->saveForm();

$I->gotoStdConsultationHome();
$I->see('PDF');
$I->seeElement('#sidebar .motionPdfCompilation');
$I->logout();



$I->wantTo('test the single-motion-PDF as a normal user');
$I->click('.motionLink3');
$I->see('PDF');
$I->see('Seltsame Zeichen: & % $ # _ { } ~ ^ \\ \\today');
$pdf = $I->downloadLink('#sidebar .download a');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}
// @TODO Try to open the file



$I->wantTo('test the single-motion-PDF from the admin interface');
$I->loginAndGotoStdAdminPage()->gotoMotionList();
$pdf = $I->downloadLink('.adminMotionTable .motion3 a.pdf');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}



$I->wantTo('test the pdf-compilation');
$I->gotoStdConsultationHome();

$pdf = $I->downloadLink('.motionPdfCompilation');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}
