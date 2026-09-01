<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$textSectionId = 'sections_' . (AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1) . '_wysiwyg';

$I->wantTo('create a statute type with a base text');
$I->loginAndGotoStdAdminPage();
$I->click('.motionTypeCreate a');
$I->checkOption('.presetStatute');
$I->fillField('#typeMotionPrefix', 'S');
$I->submitForm('.motionTypeCreateForm', [], 'create');
$I->click('.statuteCreateLnk');

$I->fillField('#sections_' . AcceptanceTester::FIRST_FREE_MOTION_SECTION, 'Our statutes');
$I->executeJS('CKEDITOR.instances.' . $textSectionId . '.setData("<ol><li>Article 1</li></ol>");');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->click('.btnBack');


$I->wantTo('create a statute amendment');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->click('#sidebar .createMotion' . AcceptanceTester::FIRST_FREE_MOTION_TYPE . ' a');

$I->wait(0.5);
$I->executeJS('CKEDITOR.instances.' . $textSectionId . '.setData("<ol><li>Paragraph 1</li></ol>");');
$I->executeJS('CKEDITOR.instances.amendmentReason_wysiwyg.setData("<p>Renaming articles to paragraphs</p>");');
$I->fillField(['name' => 'Initiator[primaryName]'], 'My Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->click('#motionConfirmedForm .btn');

$I->logout();


$I->wantTo('enable amendments to amendments for the statute type');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(AcceptanceTester::FIRST_FREE_MOTION_TYPE);
$I->checkOption('#allowAmendmentsToAmendments');
$I->submitForm('.adminTypeForm', [], 'save');
$I->logout();


$I->wantTo('create an amendment to the statute amendment');
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->click('.amendmentRow' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' a');
$I->click('#sidebar .amendmentCreate');
$I->wait(0.5);
$I->executeJS('CKEDITOR.instances.' . $textSectionId . '.setData("<ol><li>Section 1</li></ol>");');
$I->fillField(['name' => 'Initiator[primaryName]'], 'Another Name');
$I->fillField(['name' => 'Initiator[contactEmail]'], 'test2@example.org');
$I->submitForm('#amendmentEditForm', [], 'save');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->click('#motionConfirmedForm .btn');
$I->logout();


$I->wantTo('see the original and modified changes next to each other by default');
$I->gotoConsultationHome();
$I->click('.amendmentRow' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID . ' .amendmentRow' . (AcceptanceTester::FIRST_FREE_AMENDMENT_ID + 1) . ' a');

$textSectionNum = AcceptanceTester::FIRST_FREE_MOTION_SECTION + 1;
$originalHolder = '#original_section_' . $textSectionNum;
$amendingHolder = '#section_' . $textSectionNum;
$consolidatedHolder = '#consolidated_section_' . $textSectionNum;

$I->seeElement('.amendmentComparisonSection');
$I->seeElement('.amendmentComparison[data-comparison-mode="original"]');
$I->dontSeeElement('.amendmentComparison[data-comparison-mode="parent"]');

$I->see('Geänderter Antrag', $amendingHolder . ' h2');
$I->see('Section', $amendingHolder);
$I->see('Ursprünglicher Antrag', $originalHolder . ' h2');
$I->see('Paragraph', $originalHolder);


$I->wantTo('switch to the consolidated, two-layered comparison');
$I->clickJS($amendingHolder . ' .dropdown-toggle');
$I->wait(0.2);
$I->see('Änderungen gegenüber', $amendingHolder . ' .dropdown-menu .showComparisonToParent');
$I->clickJS($amendingHolder . ' .dropdown-menu .showComparisonToParent');
$I->wait(0.2);

$I->dontSeeElement('.amendmentComparison[data-comparison-mode="original"]');
$I->seeElement('.amendmentComparison[data-comparison-mode="parent"]');

$I->see('Änderungen gegenüber', $consolidatedHolder . ' h2');
$I->see('Article', $consolidatedHolder);
$I->see('Paragraph', $consolidatedHolder);
$I->see('Section', $consolidatedHolder);
$I->seeElement($consolidatedHolder . ' .outer');
$I->seeElement($consolidatedHolder . ' .amendmentComparisonLegend');


$I->wantTo('switch back to the original and modified changes shown next to each other');
$I->clickJS($consolidatedHolder . ' .dropdown-toggle');
$I->wait(0.2);
$I->see('Ursprüngliche und geänderte Fassung nebeneinander', $consolidatedHolder . ' .dropdown-menu .showComparisonToOriginal');
$I->clickJS($consolidatedHolder . ' .dropdown-menu .showComparisonToOriginal');
$I->wait(0.2);

$I->seeElement('.amendmentComparison[data-comparison-mode="original"]');
$I->dontSeeElement('.amendmentComparison[data-comparison-mode="parent"]');
$I->see('Geänderter Antrag', $amendingHolder . ' h2');
$I->see('Ursprünglicher Antrag', $originalHolder . ' h2');
