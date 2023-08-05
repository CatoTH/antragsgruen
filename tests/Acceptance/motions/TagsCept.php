<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('Ensure tags are not visible yet');
$I->gotoMotion();
$I->dontSee('Themenbereich');

$I->wantTo('Create some tags');
$I->loginAndGotoStdAdminPage()->gotoConsultation();

$I->wait(0.5);

$I->clickJS('#tagsEditForm .tagAdderBtn');
$I->fillField('#tagsEditForm .newTagRowTemplate .tagTitle input', 'Economy');

$I->submitForm('#consultationSettingsForm', [], 'save');
$I->seeInField('#tagsEditForm input', 'Economy');

$I->clickJS('#tagsEditForm .tagAdderBtn');
$I->fillField('#tagsEditForm .newTagRowTemplate .tagTitle input', 'Environment');
$I->submitForm('#consultationSettingsForm', [], 'save');
$I->seeInField('#tagsEditForm input', 'Economy');
$I->seeInField('#tagsEditForm input', 'Environment');


$I->wantTo('See the motion logged out now');
$I->logout();
$I->gotoMotion();
$I->dontSee('Themenbereich');


$I->wantTo('See the motion as a admin user now');
$I->loginAsStdAdmin();
$I->see('Themenbereich');


$I->wantTo('Add a tag');
$I->dontSeeElement('#tagAdderForm');
$I->click('.tagAdderHolder');
$I->seeElement('#tagAdderForm');
$I->selectOption('#tagAdderForm select', 'Environment');
$I->submitForm('#tagAdderForm', [], 'addTag');

$I->see('Environment', '.motionDataTable .tags');
$I->dontSeeElement('#tagAdderForm');
$I->click('.tagAdderHolder');
$I->seeElement('#tagAdderForm');
$I->selectOption('#tagAdderForm select', 'Verkehr');
$I->submitForm('#tagAdderForm', [], 'addTag');

$I->see('Verkehr', '.motionDataTable .tags');
$I->dontSeeElement('#tagAdderForm');


$I->wantTo('Delete a tag');
$I->seeElement('.motionDataTable .tags .delTag2');
$I->submitForm('.motionDataTable .tags .delTag2', [], 'delTag');
$I->dontSee('Verkehr', '.motionDataTable .tags');
$I->dontSeeElement('.motionDataTable .tags .delTag2');
$I->see('Environment', '.motionDataTable .tags');
