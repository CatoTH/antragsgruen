<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

\app\tests\_pages\MotionPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionId'         => 112,
    ]
);
$I->see('Der Antrag gehört nicht zur Veranstaltung.');
