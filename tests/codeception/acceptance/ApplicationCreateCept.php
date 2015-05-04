<?php

use tests\codeception\_pages\MotionCreatePage;

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();



// Load Form

$I->wantTo('Motion Create site loads');
MotionCreatePage::openBy(
    $I,
    [
        'subdomain'        => 'vorstandswahlen',
        'consultationPath' => 'vorstandswahlen',
        'motionTypeId'     => 4,
    ]
);

$I->see('Bewerben', 'h1');
$I->seeInTitle('Bewerben');
$I->dontSee('Voraussetzungen für einen Antrag');
$I->see('Name', 'label');
$I->see('Foto', 'label');
$I->see('Angaben', '.label');
$I->see('Selbstvorstellung', 'label');
