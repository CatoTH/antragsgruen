<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->dontSeeElement('#proposedChanges');
$I->dontSeeElement('#proposedProcedureLink');

$I->wantTo('log in');
$I->gotoConsultationHome();
$I->seeElement('.motionRow118');
$I->loginAsProposalAdmin();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeElement('#proposedChanges');


$I->wantTo('write internal comments');
$I->fillField('#proposedChanges .proposalCommentForm textarea', 'Internal comment!');
$I->executeJS('$("#proposedChanges .proposalCommentForm button").click();');
$I->wait(1);
$I->see('Internal comment!', '#proposedChanges .proposalCommentForm .commentList');


$I->wantTo('change the status to modified accepted');
$I->dontSeeCheckboxIsChecked('#proposedChanges .proposalStatus6 input');
$I->dontSeeElement('#proposedChanges .status_6');
$I->dontSeeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .proposalStatus6 input").prop("checked", true).change();');
$I->seeElement('#proposedChanges .status_6');
$I->seeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .saving button").click();');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeCheckboxIsChecked('#proposedChanges .proposalStatus6 input');
$I->dontSeeElement('#proposedChanges .saving');


$I->wantTo('edit the modification');
$I->click('#proposedChanges .editModification');
$I->wait(1);
$I->dontSeeElement('.alert-success');
$I->see('A small replacement', '#section_holder_2 ins');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData().replace(/A small/, "A really small"))');
$I->submitForm('#proposedChangeTextForm', [], 'save');
$I->seeElement('.alert-success');


$I->wantTo('make the proposal visible and notify the proposer of the amendment');
$I->executeJS('$("#proposedChanges input[name=proposalVisible]").prop("checked", true).change();');
$I->executeJS('$("#proposedChanges input[name=notifyProposer]").prop("checked", true).change();');
$I->executeJS('$("#votingBlockId").selectlist("selectByValue", "NEW").trigger("changed.fu.selectlist")');
$I->fillField('#newBlockTitle', 'Voting 1');
$I->executeJS('$("#proposedChanges .saving button").click();');
$I->wait(1);
$I->see('Noch keine Bestätigung erfolgt', '#proposedChanges .notificationStatus');
$I->assertEquals('Voting 1', $I->executeJS('return $("#votingBlockId").selectlist("selectedItem").text'));


$I->wantTo('propose to reject the second amendment');
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->seeElement('#proposedChanges .collission279');
$I->executeJS('$("#proposedChanges .proposalStatus5 input").prop("checked", true).change();');
$I->executeJS('$("#proposedChanges input[name=notifyProposer]").prop("checked", true).change();');
// Not making it visible yet
$I->seeElement('#proposedChanges .saving');
$I->executeJS('$("#proposedChanges .saving button").click();');


$I->wantTo('make the proposal page visible');
$I->gotoConsultationHome();
$I->logout();
$I->loginAndGotoStdAdminPage()->gotoConsultation();
$I->checkOption('#proposalProcedurePage');
$I->submitForm('#consultationSettingsForm', [], 'save');

$I->wantTo('see the proposal page');
$I->gotoConsultationHome();
$I->logout();
$I->seeElement('#proposedProcedureLink');
$I->click('#proposedProcedureLink');
$I->see('Voting 1', '.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID);
$I->seeElement('.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .amendment279');
$I->dontSeeElement('.votingTable' . AcceptanceTester::FIRST_FREE_VOTING_BLOCK_ID . ' .amendment280');


$I->wantTo('agree to the first proposal, but not the second one');
$I->loginAsStdUser();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 280);
$I->seeElement('.agreeToProposal');
// Don't agree
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);
$I->seeElement('.agreeToProposal');
$I->submitForm('.agreeToProposal', [], 'setProposalAgree');
$I->seeElement('.alert-success');


$I->wantTo('see the agreement as admin');
$I->logout();
$I->loginAsProposalAdmin();
$I->seeElement('.notificationSettings .accepted');

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

$I->see('A really small replacement', '#section_holder_2 ins');
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->submitForm('.motionMergeForm', [], 'save');
$I->see('A really small replacement');
$I->dontSee('A big replacement');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->submitForm('#motionConfirmedForm', []);
$I->see('A really small replacement');

$I->see('A8neu', 'h1');
$I->see('Umwelt', '.motionDataTable');
$I->gotoConsultationHome();
$I->see('A8neu');
$I->dontSeeElement('.motionRow118');
