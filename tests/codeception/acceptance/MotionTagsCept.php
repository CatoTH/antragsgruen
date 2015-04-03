<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();


$I->wantTo('Ensure tags are not visible yet');
$I->gotoStdMotion(true);
$I->dontSee('Themenbereiche');

$I->loginAsStdAdmin();
$I->gotoStdMotion(true);
$I->dontSee('Themenbereiche');


$I->wantTo('Create some tags');
$I->click('#adminLink');
$I->click('#consultationextendedLink');

$I->dontSee('Economy');
$I->dontSee('Environment');
$I->dontSeeElement('.tagCreateInput');
$I->click('.tagCreateOpener');
$I->seeElement('.tagCreateInput');

$I->fillField('.tagCreateInput', 'Economy');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->see('Economy');

$I->dontSeeElement('.tagCreateInput');
$I->click('.tagCreateOpener');
$I->fillField('.tagCreateInput', 'Environment');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->see('Economy');
$I->see('Environment');



$I->wantTo('See the motion logged out now');
$I->logout();
$I->gotoStdMotion();
$I->dontSee('Themenbereiche');


$I->wantTo('See the motion as a admin user now');
$I->loginAsStdAdmin();
$I->see('Themenbereiche');


$I->wantTo('Add a tag');
$I->dontSeeElement('#tagAdderForm');
$I->click('.tagAdderHolder');
$I->seeElement('#tagAdderForm');
$I->selectOption('#tagAdderForm select', 'Environment');
$I->submitForm('#tagAdderForm', [], 'motionAddTag');

$I->see('Environment', '.motionDataTable .tags');
$I->dontSeeElement('#tagAdderForm');
