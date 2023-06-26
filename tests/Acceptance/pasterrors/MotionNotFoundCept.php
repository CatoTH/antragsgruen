<?php

/** @var \Codeception\Scenario $scenario */
use Tests\_pages\MotionPage;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->openPage(MotionPage::class, [
    'subdomain'        => 'stdparteitag',
    'consultationPath' => 'std-parteitag',
    'motionSlug'       => '112',
]);
$I->see('Antrag nicht gefunden.');
