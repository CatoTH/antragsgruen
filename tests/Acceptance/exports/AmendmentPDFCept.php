<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('test a single amendment PDF as a user');
$I->gotoConsultationHome();
$I->click('.amendment1');
$pdf = $I->downloadLink('#sidebar .download a');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}


$I->wantTo('test a single amendment PDF from the admin interface');
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->click('#motionListLink');
$pdf = $I->downloadLink('.adminMotionTable .amendment1 a.pdf');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}


$I->wantTo('test amendment PDF compilation from home page');
$I->gotoConsultationHome();
$pdf = $I->downloadLink('#sidebar .amendmentPdfs');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}


$I->wantTo('test amendment PDF compilation from the admin interface');
$I->gotoMotionList();
$I->click('#exportAmendmentsBtn');
$pdf = $I->downloadLink('.amendmentPDF');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}
