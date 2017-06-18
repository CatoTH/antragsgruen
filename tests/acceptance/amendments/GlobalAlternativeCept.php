<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$createPage = $I->gotoConsultationHome()->gotoAmendmentCreatePage(2);

$createPage->fillInValidSampleData('alternative motion');
$I->executeJS("CKEDITOR.instances.sections_2_wysiwyg.setData('<p>This is my new motion</p>');");
$I->executeJS("CKEDITOR.instances.sections_4_wysiwyg.setData('<p>Part 2</p>');");
$I->checkOption('input[name=globalAlternative]');

$createPage->saveForm();
$I->see('This is my new motion');
$I->dontSee('Woibbadinga');
$I->submitForm('#amendmentConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', [], null);

$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, 'ul.amendments');
$I->dontSee(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, '.bookmarks');

$I->click('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('This is my new motion');
$I->see('Part 2');
$I->dontSee('Woibbadinga');


$admin = $I->loginAndGotoMotionList()->gotoAmendmentEdit(AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->see('This is my new motion');
$I->see('Part 2');
$I->dontSee('Woibbadinga');
$I->seeCheckboxIsChecked('#globalAlternative');
$I->uncheckOption('#globalAlternative');
$admin->saveForm();

$I->dontSeeCheckboxIsChecked('#globalAlternative');
$I->see('This is my new motion', '.inserted');
$I->see('Part 2', '.inserted');
$I->see('Woibbadinga', '.deleted');


$I->click('.sidebarActions .view');
$I->see('This is my new motion', '.inserted');
$I->see('Part 2', '.inserted');
$I->see('Woibbadinga', '.deleted');
$I->gotoMotion();
$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, 'ul.amendments');
$I->see(AcceptanceTester::FIRST_FREE_AMENDMENT_TITLE_PREFIX, '.bookmarks');

$I->gotoAmendment(true, 2, AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->click('#sidebar .adminEdit a');
$I->checkOption('#globalAlternative');
$I->submitForm('#amendmentUpdateForm', [], 'save');

$I->gotoMotion();
$I->click('#sidebar .mergeamendments a');
$I->seeCheckboxIsChecked('.amendment276');
$I->dontSeeCheckboxIsChecked('.amendment' . AcceptanceTester::FIRST_FREE_AMENDMENT_ID);

$I->gotoAmendment(true, '2', AcceptanceTester::FIRST_FREE_AMENDMENT_ID);
$I->click('#sidebar .mergeIntoMotion a');
$status = $I->executeJS('return $("#otherAmendmentsStatus1").selectlist("selectedItem").value');
$I->assertEquals(\app\models\db\Amendment::STATUS_REJECTED, $status);
