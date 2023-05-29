<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();


$I->wantTo('Ensure tags are not visible yet');
$I->gotoMotion(true);
$I->dontSee('Themenbereich');

$I->wantTo('Allow selecting multiple tags');
$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->cantSeeCheckboxIsChecked('#allowMultipleTags');
$I->checkOption('#allowMultipleTags');
$page->saveForm();
$I->canSeeCheckboxIsChecked('#allowMultipleTags');

$I->wantTo('Create a motion with multiple tags');
$page = $I->gotoConsultationHome()->gotoMotionCreatePage();
$I->see('Umwelt', '.multipleTagsGroup');
$I->see('Verkehr', '.multipleTagsGroup');
$I->checkOption("//input[@name='tags[]'][@value='1']"); // Umwelt
$I->checkOption("//input[@name='tags[]'][@value='10']"); // Soziales
$page->fillInValidSampleData();
$page->saveForm();
$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->wantTo('confirm it has multiple tags now');
$I->gotoConsultationHome()->gotoMotionView(AcceptanceTester::FIRST_FREE_MOTION_ID);
$I->see('Umwelt', '.tags');
$I->see('Soziales', '.tags');
$I->dontSee('Verkehr', '.tags');
