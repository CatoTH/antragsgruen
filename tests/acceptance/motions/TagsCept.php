<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('Ensure tags are not visible yet');
$I->gotoMotion(true);
$I->dontSee('Themenbereiche');

$I->wantTo('Create some tags');
$I->loginAndGotoStdAdminPage()->gotoConsultation();

if ($I->executeJS('return $("#tagsList").pillbox("items").length') != 3) {
    $I->fail('Invalid return from tag-List');
}
$I->executeJS('$("#tagsList").pillbox("addItems", -1, [{ "text": "Economy" }]);');

$I->submitForm('#consultationSettingsForm', [], 'save');
$I->see('Economy');

$I->executeJS('$("#tagsList").pillbox("addItems", -1, [{ "text": "Environment" }]);');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->see('Economy');
$I->see('Environment');



$I->wantTo('See the motion logged out now');
$I->logout();
$I->gotoMotion();
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
$I->click('.tagAdderHolder');
$I->seeElement('#tagAdderForm');
$I->selectOption('#tagAdderForm select', 'Verkehr');
$I->submitForm('#tagAdderForm', [], 'motionAddTag');

$I->see('Verkehr', '.motionDataTable .tags');
$I->dontSeeElement('#tagAdderForm');


$I->wantTo('Delete a tag');
$I->seeElement('.motionDataTable .tags .delTag2');
$I->submitForm('.motionDataTable .tags .delTag2', [], 'motionDelTag');
$I->dontSee('Verkehr', '.motionDataTable .tags');
$I->dontSeeElement('.motionDataTable .tags .delTag2');
$I->see('Environment', '.motionDataTable .tags');
