<?php

/** @var \Codeception\Scenario $scenario */
use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('merge an amendment');

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoAmendment(true, 2, 274);
$I->click('#sidebar .mergeIntoMotion a');
$I->wait(1);
$I->selectOption('#amendmentStatus', IMotion::STATUS_MODIFIED_ACCEPTED);
$I->selectOption('#otherAmendmentsStatus1', IMotion::STATUS_REJECTED);
$I->executeJS('$(".save-row .goto_2").click();');
$I->wait(1);
$I->click('.checkAmendmentCollisions');
$I->wait(1.5);

$I->wantTo('see the collisions');
$I->seeElement('.amendmentCollisionsHolder .alert-danger');
$I->see('Wui helfgod Wiesn', 'del');
$I->see('Alternatives Ende', 'ins');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A2', 'h1');
$I->see('Version 2', '.motionDataTable .historyOpener .currVersion');
$I->see('Alternatives Ende');
$I->dontSee('Xaver Prosd eana an a bravs');
$I->see('Ä2');
$I->see('Ä3');
$I->dontSee('Ä1');
$I->dontSee('Ä6');

$I->gotoMotion(false, 2);
$I->seeElement('.alert-danger.motionReplacedBy');


$I->wantTo('try to merge another amendment');
$I->gotoAmendment(true, AcceptanceTester::FIRST_FREE_MOTION_ID, 272);
$I->click('#sidebar .mergeIntoMotion a');
$I->wait(1);
$I->click('.save-row .goto_2');
$I->wait(1);
$I->dontSeeElement('.versionSelector');
$I->executeJS('$(".modifySelector input").click();');
$I->wait(1);
$I->executeJS('CKEDITOR.instances.new_paragraphs_original_2_7.setData(CKEDITOR.instances.new_paragraphs_original_2_7.getData() + "<p>A modified adaption</p>");');

$I->click('.checkAmendmentCollisions');
$I->wait(1);
$I->seeElement('.amendmentCollisionsHolder .alert-success');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A2', 'h1');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->see('Version 2', '.motionDataTable .motionHistory a');
$I->see('Version 3', '.motionDataTable .motionHistory .currVersion');
$I->see('A modified adaption', 'p');
$I->see('Something dahoam');
$I->dontSee('Ä4');
