<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();
$I->loginAsProposalAdmin();
$I->gotoMotion(true, 'Testing_proposed_changes-630');

$I->dontSeeElement('#proposedChanges');
$I->clickJS('.proposedChangesOpener button');
$I->seeElement('#proposedChanges');
$I->dontSeeElement('#pp_section_2_0');


$I->wantTo('write internal comments');
$I->fillField('#proposedChanges .proposalCommentForm textarea', 'Internal comment!');
$I->executeJS('$("#proposedChanges .proposalCommentForm button").click();');
$I->wait(1);
$I->see('Internal comment!', '#proposedChanges .proposalCommentForm .commentList');


$I->wantTo('change the status to modified accepted');
$I->dontSeeCheckboxIsChecked('#proposedChanges .proposalStatus' . IMotion::STATUS_MODIFIED_ACCEPTED . ' input');
$I->dontSeeElement('#proposedChanges .status_' . IMotion::STATUS_MODIFIED_ACCEPTED);
$I->dontSeeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_MODIFIED_ACCEPTED . ' input").prop("checked", true).change();');
$I->seeElement('#proposedChanges .status_' . IMotion::STATUS_MODIFIED_ACCEPTED);
$I->seeElement('#proposedChanges .saving');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);


$I->wantTo('edit the modification');
$I->see('Lorem ipsum dolor sit amet', '#section_holder_2');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/Lorem ipsum dolor sit amet/, "Vegetable ipsum dolor sit amet"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->seeElement('.alert-success');
$I->wait(1);

$I->wantTo('make the proposal visible and notify the proposer of the amendment');
$I->executeJS('$("#proposedChanges input[name=proposalVisible]").prop("checked", true).change();');
$I->executeJS('$("#votingBlockId").val("NEW").trigger("change")');
$I->fillField('#newBlockTitle', 'Voting 1');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);
$I->see('Über den Vorschlag informieren und Bestätigung einholen', '#proposedChanges .notificationStatus');
$I->dontSeeElement('.notifyProposerSection');
$I->clickJS('#proposedChanges button.notifyProposer');
$I->wait(1);
$I->seeElement('.notifyProposerSection');
$I->clickJS('#proposedChanges button[name=notificationSubmit]');
$I->wait(1);
$I->see('Der/die Antragsteller*in wurde am');


$I->assertEquals('Voting 1', $I->executeJS('return $("#votingBlockId option:selected").text()'));


$I->wantTo('make the proposal page visible');
$I->gotoConsultationHome();
$I->logout();
$page = $I->loginAndGotoStdAdminPage()->gotoAppearance();
$I->checkOption('#proposalProcedurePage');
$page->saveForm();

$I->wantTo('see the proposal page');
$I->gotoConsultationHome();
$I->logout();
$I->seeElement('#proposedProcedureLink');
$I->click('#proposedProcedureLink');
$I->see('Voting 1', '.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID);
$I->seeElement('.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .motion118');


$I->wantTo('agree to the proposal');
$I->loginAsStdUser();
$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->see('Vegetable', '#pp_section_2_0 ins');
$I->seeElement('.agreeToProposal');
$I->submitForm('.agreeToProposal', [], 'setProposalAgree');
$I->seeElement('.alert-success');


$I->wantTo('see the agreement as admin');
$I->logout();
$I->loginAsProposalAdmin();
$I->seeElement('.notificationSettings .accepted');


$I->logout();
$I->loginAsConsultationAdmin();
$I->click('#sidebar .mergeamendments a');
$I->submitForm('.mergeAllRow', []);
$I->wait(1);

$I->see('Vegetable', '#sections_2_0_wysiwyg ins');
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->submitForm('.motionMergeForm', [], 'save');
$I->see('Vegetable');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', []);
$I->see('Vegetable');

$I->see('Testing proposed changes', 'h1');
$I->see('Version 2', '.motionHistory');
$I->gotoConsultationHome();
$I->see('Testing proposed changes', '.motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
