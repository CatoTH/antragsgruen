<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$page = \app\tests\_pages\MotionCreatePage::openBy(
    $I,
    [
        'subdomain'        => 'bdk',
        'consultationPath' => 'bdk',
        'motionTypeId'     => 7,
    ]
);
$I->dontSee(mb_strtoupper('Antrag stellen'), 'h1');
$I->see(mb_strtoupper('Login'), 'h1');
