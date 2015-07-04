<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$scenario->incomplete('Not implemented yet');

$I->wantTo('test the single-motion-TXT from the admin interface');
$I->loginAndGotoStdAdminPage()->gotoMotionList();
$txt = $I->downloadLink('.adminMotionTable .motion3 a.txt');
if (strlen($txt) == 0) {
    $I->fail('TXT has no content');
}
