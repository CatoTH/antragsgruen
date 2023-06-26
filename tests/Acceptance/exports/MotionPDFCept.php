<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('disable PDFs');
$I->gotoConsultationHome();


$I->loginAsStdAdmin();
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->click(".layout.php-1");
$motionTypePage->saveForm();

$I->gotoConsultationHome();
$I->dontSee('PDF');
$I->dontSeeElement('#sidebar .motionPdfCompilation');
$I->click('.motionLink2');
$I->dontSee('PDF');



$I->wantTo('activate PDFs again');
$motionTypePage = $I->gotoStdAdminPage()->gotoMotionTypes(1);
$I->click(".layout.php0");
$motionTypePage->saveForm();

$I->gotoConsultationHome();
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
$I->loginAndGotoMotionList();
$pdf = $I->downloadLink('.adminMotionTable .motion3 a.pdf');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}



$I->wantTo('test the pdf-compilation');
$I->gotoConsultationHome();

$pdf = $I->downloadLink('.motionPdfCompilation');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}
