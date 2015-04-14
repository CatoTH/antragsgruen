<?php

use tests\codeception\_pages\MotionPage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();


// Load Form

$I->wantTo('Open the amendment creation page');
MotionPage::openBy(
    $I,
    [
        'subdomain'        => 'stdparteitag',
        'consultationPath' => 'std-parteitag',
        'motionId'         => 2,
    ]
);
$I->see('A2: O’ZAPFT IS!', 'h1');
$I->see('Änderungsantrag stellen', '.sidebarActions');
$I->click('.sidebarActions .amendmentCreate a');
