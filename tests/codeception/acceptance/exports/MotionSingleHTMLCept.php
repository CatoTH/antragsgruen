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
$I->click('.adminMotionTable .motion3 a.plainHtml');
$I->see('Seltsame Zeichen: & % $ # _ { } ~ ^ \\ \\today');
