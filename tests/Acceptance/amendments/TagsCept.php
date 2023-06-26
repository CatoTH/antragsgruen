<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('activate tags for amendments');
$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->dontSeeElement('.multipleTagsGroup');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->dontSeeCheckboxIsChecked('#amendmentsHaveTags');
$I->checkOption('#amendmentsHaveTags');
$I->checkOption('#allowMultipleTags');
$page->saveForm();
$I->seeCheckboxIsChecked('#amendmentsHaveTags');


$I->wantTo('Create an amendment with a tag');
$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->see('Soziales', '.multipleTagsGroup');
$I->checkOption("//input[@name='tags[]'][@value='10']"); // Soziales
$page->createAmendment('Test', true);


$I->wantTo('Confirm the tag is visible');
$I->gotoConsultationHome()->gotoAmendmentView(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Soziales', '.motionDataTable .tags');

$I->gotoMotionList();
$I->see('Soziales', '.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .tagsCol');
