<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoMotionCreatePage('bdk', 'bdk', 7);
$I->dontSee(mb_strtoupper('Antrag stellen'), 'h1');
$I->see(mb_strtoupper('Login'), 'h1');
