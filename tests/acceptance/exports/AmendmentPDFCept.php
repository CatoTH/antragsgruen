<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('test a single amendment PDF as a user');
$I->gotoStdConsultationHome();
$I->click('.amendment1');
$pdf = $I->downloadLink('#sidebar .download a');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}


$I->wantTo('test a single amendment PDF from the admin interface');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('.motionListAll');
$pdf = $I->downloadLink('.adminMotionTable .amendment1 a.pdf');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}


$I->wantTo('test amendment PDF compilation');
$I->gotoStdConsultationHome();
$scenario->skip('Not implemented yet');

$pdf = $I->downloadLink('#sidebar .amendmentPdfs');
if (strlen($pdf) == 0) {
    $I->fail('PDF has no content');
}
