<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('activate tags for amendments');
$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->dontSeeElement('.multipleTagsGroup');

$page = $I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->seeElement('#tagsEditForm .editList0');
$I->dontSeeElement('#tagsEditForm .editList2');
$I->clickJS('.tagTypeSelector input[value=\"2\"]');
$I->dontSeeElement('#tagsEditForm .editList0');
$I->seeElement('#tagsEditForm .editList2');
$I->clickJS('#tagsEditForm .adderRow button');
$I->executeJS('$("#tagsEditForm .editList2 input").last().val("Social Issues")');
$I->clickJS('#tagsEditForm .adderRow button');
$I->executeJS('$("#tagsEditForm .editList2 input").last().val("Environmental Issues")');
$I->clickJS('#tagsEditForm .adderRow button');
$I->executeJS('$("#tagsEditForm .editList2 input").last().val("Medical Issues")');

$I->checkOption('#allowMultipleTags');
$page->saveForm();

$I->clickJS('.tagTypeSelector input[value=\"2\"]');

$I->wantTo('Create an amendment with a tag');
$page = $I->gotoConsultationHome()->gotoAmendmentCreatePage();
$I->see('Social Issues', '.multipleTagsGroup');
$I->checkOption("//input[@name='tags[]'][@value='" . AcceptanceTester::FIRST_FREE_TAG_ID . "']"); // Social Issues
$page->createAmendment('Test', true);


$I->wantTo('Confirm the tag is visible');
$I->gotoConsultationHome()->gotoAmendmentView(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('Social Issues', '.motionDataTable .tags');

$I->gotoMotionList();
$I->see('Social Issues', '.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .tagsCol');
