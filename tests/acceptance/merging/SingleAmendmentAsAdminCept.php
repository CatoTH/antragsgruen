<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('merge an amendment');

$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoAmendment(true, 2, 274);
$I->click('.mergeIntoMotion a');
$I->wait(1);
$I->executeJS('$("#amendmentStatus").selectlist("selectByValue", "6");');
$I->executeJS('$("#otherAmendmentsStatus1").selectlist("selectByValue", "5");');
$I->executeJS('$(".save-row .goto_2").click();');
$I->wait(1);
$I->click('.checkAmendmentCollissions');
$I->wait(1);

$I->wantTo('see the collissions');
$I->seeElement('.amendmentCollissionsHolder .alert-danger');
$I->see('Wui helfgod Wiesn', 'del');
$I->see('Alternatives Ende', 'ins');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A2neu', 'h1');
$I->see('A2: ', '.replacesMotion');
$I->see('Alternatives Ende');
$I->dontSee('Xaver Prosd eana an a bravs');
$I->see('Ä2');
$I->see('Ä3');
$I->dontSee('Ä1');
$I->dontSee('Ä6');

$I->gotoMotion(false, 2);
$I->seeElement('.alert-danger.motionReplayedBy');


$I->wantTo('try to merge another amendment');
$I->gotoAmendment(true, AcceptanceTester::FIRST_FREE_MOTION_ID, 272);
$I->click('.mergeIntoMotion a');
$I->wait(1);
$I->click('.save-row .goto_2');
$I->wait(1);
$I->executeJS('$(".modifySelector input").eq(1).click();');
$I->executeJS('CKEDITOR.instances.new_paragraphs_2_7.setData(CKEDITOR.instances.new_paragraphs_2_7.getData() + "<p>A modified adaption</p>");');

$I->click('.checkAmendmentCollissions');
$I->wait(1);
$I->seeElement('.amendmentCollissionsHolder .alert-success');
$I->submitForm('#amendmentMergeForm', [], 'save');
$I->see('Der Änderungsantrag wurde eingepflegt.', '.alert-success');

$I->wantTo('check the changes were made');
$I->click('.alert-success .btn-primary');
$I->see('A2neu2', 'h1');
$I->see('A2neu: ', '.replacesMotion');
$I->see('A modified adaption', 'p');
$I->see('Something dahoam');
$I->dontSee('Ä4');