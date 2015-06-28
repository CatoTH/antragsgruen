<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the single-motion-TXT from the admin interface');
$I->gotoStdConsultationHome();
$I->loginAsStdAdmin();
$I->click('#adminLink');
$I->click('.motionListAll');
$txt = $I->downloadLink('.adminMotionTable .motion3 a.txt');
if (strlen($txt) == 0) {
    $I->fail('TXT has no content');
}
