<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the single-motion-ODT from the admin interface');
$I->loginAndGotoMotionList();
$odt = $I->downloadLink('.adminMotionTable .motion3 a.odt');
if (strlen($odt) == 0) {
    $I->fail('ODT has no content');
}
