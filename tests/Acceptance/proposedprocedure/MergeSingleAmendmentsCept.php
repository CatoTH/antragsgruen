<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('merge an amendment');

$I->gotoConsultationHome();

// Remove relicts from previous test cases
$I->executeJS('for (let key in localStorage) localStorage.removeItem(key);');

$I->loginAsStdAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 281);
$I->see('Zombie', 'ins');

$I->click('#sidebar .mergeIntoMotion a');
$I->wait(1);

$I->click('.save-row .goto_2');
$I->wait(1);
$I->seeElement('.versionSelector');

$I->wantTo('see the different versions');
$I->seeCheckboxIsChecked("//input[@name='version_2_2'][@value='modified']");
$I->see('Zombie', '.modifiedVersion.motionTextHolder ins');
$I->dontSeeElement('.originalVersion.motionTextHolder');

$I->executeJS('$("input[name=\"version_2_2\"][value=\"original\"]").click();');
$I->dontSeeElement('.modifiedVersion.motionTextHolder');
$I->seeElement('.originalVersion.motionTextHolder');

$I->executeJS('$(".modifySelector input").click();');
$I->seeElement('.originalVersion.modifyText');

$I->executeJS('$("input[name=\"version_2_2\"][value=\"modified\"]").click();');
$I->seeElement('.modifiedVersion.modifyText');

$I->wait(1);
$I->executeJS('CKEDITOR.instances.new_paragraphs_modified_2_2.setData(CKEDITOR.instances.new_paragraphs_modified_2_2.getData() + "<p>A modified adaption</p>");');

$I->click('.checkAmendmentCollisions');
$I->wait(1);
$I->seeElement('.amendmentCollisionsHolder .alert-success');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A8', 'h1');
$I->seeElement('.motionDataTable .btnHistoryOpener');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->see('Version 1', '.motionDataTable .motionHistory a');
$I->see('A modified adaption');
$I->see('Zombie ipsum');
