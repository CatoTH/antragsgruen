<?php

/** @var \Codeception\Scenario $scenario */

use app\models\db\IMotion;
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->initializeAndGoHome();

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->dontSeeElement('#proposedChanges');
$I->dontSeeElement('#proposedProcedureLink');

$I->wantTo('log in');
$I->gotoConsultationHome();
$I->seeElement('.motionRow118');
$I->loginAsProposalAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->dontSeeElement('#proposedChanges');
$I->clickJS('.proposedChangesOpener button');
$I->seeElement('#proposedChanges');


$I->wantTo('write internal comments');
$I->fillField('#proposedChanges .proposalCommentForm textarea', 'Internal comment!');
$I->clickJS('#proposedChanges .proposalCommentForm .btnSubmit');
$I->wait(0.5);
$I->see('Internal comment!', '#proposedChanges .proposalCommentForm .commentList');


$I->wantTo('change the status to modified accepted');
$I->dontSeeCheckboxIsChecked('#proposedChanges .proposalStatus' . IMotion::STATUS_MODIFIED_ACCEPTED . ' input');
$I->dontSeeElement('#proposedChanges .status_' . IMotion::STATUS_MODIFIED_ACCEPTED);
$I->dontSeeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_MODIFIED_ACCEPTED . ' input").prop("checked", true).change();');
$I->wait(0.1);
$I->dontSeeElement('#proposedChanges .status_' . IMotion::STATUS_MODIFIED_ACCEPTED);
$I->seeElement('#proposedChanges .saving');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);


$I->wantTo('edit the modification');
$I->see('A small replacement', '#section_holder_2 ins');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/A small/, "A really small"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->seeElement('.alert-success');
$I->wait(1);

$I->wantTo('make the proposal visible and notify the proposer of the amendment');
$I->seeElement('#proposedChanges .status_' . IMotion::STATUS_MODIFIED_ACCEPTED);
$I->executeJS('$("#proposedChanges input[name=proposalVisible]").prop("checked", true).change();');
$I->executeJS('$("#votingBlockId").val("NEW").trigger("change")');
$I->fillField('#newBlockTitle', 'Voting 1');
$I->seeInField('#proposedChanges input[name=newVersion]', 'current');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);
$I->dontSeeElement('.proposalHistory');

$I->see('Über den Vorschlag informieren und Bestätigung einholen', '#proposedChanges .notificationStatus');
$I->dontSeeElement('.notifyProposerSection');
$I->clickJS('#proposedChanges button.notifyProposer');
$I->wait(1);
$I->seeElement('.notifyProposerSection');
$stdText = $I->grabTextFrom('#proposedChanges textarea[name=proposalNotificationText]');
$I->fillField('#proposedChanges textarea[name=proposalNotificationText]', $stdText . "\nADDITIONAL TEXT 123");
$I->clickJS('#proposedChanges button[name=notificationSubmit]');
$I->wait(1);
$I->see('Der/die Antragsteller*in wurde am');
$I->see('ADDITIONAL TEXT 123', '#proposedChanges .proposalCommentForm .commentList');


$I->assertEquals('Voting 1', $I->executeJS('return $("#votingBlockId option:selected").text()'));


$I->wantTo('propose to reject the second amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->seeElement('#proposedChanges .collision279');
$I->executeJS('$("#proposedChanges .proposalStatus' . IMotion::STATUS_REJECTED . ' input").prop("checked", true).change();');
$I->clickJS('#proposedChanges .saving button');
$I->wait(1);

$I->dontSee('Der/die Antragsteller*in wurde am');
$I->dontSeeElement('.notifyProposerSection');
$I->clickJS('#proposedChanges button.notifyProposer');
// Not making it visible yet
$I->wait(1);
$I->seeElement('.notifyProposerSection');
$stdText = $I->grabTextFrom('#proposedChanges textarea[name=proposalNotificationText]');
$I->fillField('#proposedChanges textarea[name=proposalNotificationText]', $stdText . "\nADDITIONAL TEXT 123");
$I->clickJS('#proposedChanges button[name=notificationSubmit');
$I->wait(1);
$I->see('Der/die Antragsteller*in wurde am');
$I->see('ADDITIONAL TEXT 123', '#proposedChanges .proposalCommentForm .commentList');

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
$I->seeElement('.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .amendment279');
$I->dontSeeElement('.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .amendment280');


$I->wantTo('Disagree to one propsal');
$I->loginAsStdUser();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->seeElement('.agreeToProposal');
$I->fillField('.agreeToProposal textarea[name=comment]', 'No, disagree');
$I->submitForm('.agreeToProposal', [], 'setProposalDisagree');
$I->seeElement('.alert-success');
$I->seeElement('.agreeToProposal .commentList .disagreed');
$I->see('No, disagree', '.agreeToProposal .commentList');


$I->wantTo('Agree to the second one');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeElement('.agreeToProposal');
$I->see('ADDITIONAL TEXT 123', '.agreeToProposal');
$I->submitForm('.agreeToProposal', [], 'setProposalAgree');
$I->seeElement('.alert-success');


$I->wantTo('see the agreement / disagreement as admin');
$I->logout();
$I->loginAsProposalAdmin();
$I->seeElement('.notificationSettings .accepted');
$I->dontSeeElement('.notificationSettings .rejected');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->dontSeeElement('.notificationSettings .accepted');
$I->seeElement('.notificationSettings .rejected');

$I->wantTo('test the motion list');
$I->gotoMotionList();
$I->seeElement('.amendment279 .visible');
$I->dontSeeElement('.amendment279 .notVisible');
$I->seeElement('.amendment280 .notVisible');
$I->dontSeeElement('.amendment280 .visible');

$I->wantTo('make the second proposal visible');
$I->checkOption('.amendment280 .selectbox');
$I->submitForm('.motionListForm', [], 'proposalVisible');
$I->dontSeeElement('.amendment280 .notVisible');
$I->seeElement('.amendment280 .visible');


$I->wantTo('merge the amendment into the motion');
$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->see('Umwelt', '.motionDataTable');
$I->dontSeeElement('#sidebar .mergeamendments');

$I->logout();
$I->loginAsConsultationAdmin();
$I->click('#sidebar .mergeamendments a');
$I->seeCheckboxIsChecked('.amendment279 .textProposal input');
$I->dontSeeElement('.amendment280 .textProposal');
$I->uncheckOption('.amendment280 .colCheck input');
$I->submitForm('.mergeAllRow', []);
$I->wait(1);

$I->see('A really small replacement', '#sections_2_1_wysiwyg ins');
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->submitForm('.motionMergeForm', [], 'save');
$I->see('A really small replacement');
$I->dontSee('A big replacement');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', []);
$I->see('A really small replacement');

$I->see('Testing proposed changes', 'h1');
$I->see('Version 2', '.motionDataTable .historyOpener .currVersion');
$I->clickJS('.motionDataTable .btnHistoryOpener');
$I->see('Version 1', '.motionDataTable .motionHistory a');
$I->see('Umwelt', '.motionDataTable');
$I->gotoConsultationHome();
$I->see('Testing proposed changes', '.sectionResolutions .motionLink' . (AcceptanceTester::FIRST_FREE_MOTION_ID + 1));
