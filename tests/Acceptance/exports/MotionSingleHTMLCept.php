<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the single-motion-HTML from the admin interface');
$I->loginAndGotoMotionList();
$I->click('.adminMotionTable .motion3 a.plainHtml');
$I->see('Seltsame Zeichen: & % $ # _ { } ~ ^ \\ \\today');
