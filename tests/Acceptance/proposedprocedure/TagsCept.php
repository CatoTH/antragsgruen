<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('set some tags');
$I->gotoConsultationHome();
$I->loginAsProposalAdmin();

// Remove relicts from previous test cases
$I->executeJS('for (let key in localStorage) localStorage.removeItem(key);');


// <pseudotag> and Äöé\' and some nice words to A2
$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->dontSeeElement('#proposedChanges');
$I->clickJS('.proposedChangesOpener button');
$I->seeElement('#proposedChanges');
$I->dontSeeElement('#proposedChanges .saving');

$I->executeJS('$(".proposalTagsSelect")[0].selectize.createItem("<pseudotag>")');
$I->executeJS('$(".proposalTagsSelect")[0].selectize.createItem("Äöé\\\\\\\'")');
$I->executeJS('$(".proposalTagsSelect")[0].selectize.createItem("我爱你😀")');

$I->seeElement('#proposedChanges .saving');
$I->dontSeeElement('#proposedChanges .saved');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.3);
$I->seeElement('#proposedChanges .saved');


// Verkehr and some nice words to Ä1 to A8 (note that Verkehr is also a regular tag)
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 279);

$I->seeElement('#proposedChanges');
$I->dontSeeElement('#proposedChanges .saving');

$I->executeJS('$(".proposalTagsSelect")[0].selectize.createItem("Verkehr")');
$I->executeJS('$(".proposalTagsSelect")[0].selectize.createItem("我爱你😀")');

$I->seeElement('#proposedChanges .saving');
$I->dontSeeElement('#proposedChanges .saved');
$I->clickJS('#proposedChanges .saving button');
$I->wait(0.3);
$I->seeElement('#proposedChanges .saved');

// Make sure the tests are not visible in the regular tag list

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->see('Umwelt', '.motionDataTable');
$I->dontSee('pseudotag', '.motionDataTable');
$I->see('pseudotag', '#proposedChanges');

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->seeInPageSource('Umwelt');
$I->dontSeeInPageSource('pseudotag');

$I->wantTo('test the filter in the motion list');
$I->click('#motionListLink');

$I->see('<pseudotag>', '.motion118 .tagsCol');
$I->see('Äöé\\\'', '.motion118 .tagsCol');
$I->see('我爱你😀', '.motion118 .tagsCol');

$I->seeElement('.motion118');
$I->seeElement('.motion2');
$I->seeElement('.amendment279');
$I->seeElement('.amendment280');

$I->selectOption('#filterSelectTags', AcceptanceTester::FIRST_FREE_TAG_ID + 2);
$I->submitForm('.motionListSearchForm', [], 'search');

$I->seeElement('.motion118');
$I->dontSeeElement('.motion2');
$I->seeElement('.amendment279');
$I->dontSeeElement('.amendment280');

$I->wantTo('test the filter in the proposed procedure list');
$I->click('#exportProcedureBtn');
$I->click('.exportProcedureDd .linkProcedureIntern a');
$I->seeElement('.proposedProcedureOverview');

$I->see('我爱你😀', '.tagList');
$I->see('Verkehr', '.tagList');
$I->see('Äöé\\\'', '.tagList');
$I->see('<pseudotag>', '.tagList');
$I->dontSee('Umwelt', '.tagList');

$I->seeElement('.motion118');
$I->seeElement('.motion2');
$I->seeElement('.amendment279');
$I->seeElement('.amendment280');

$I->click('.tagList .tag' . (AcceptanceTester::FIRST_FREE_TAG_ID + 2));

$I->seeElement('.motion118');
$I->dontSeeElement('.motion2');
$I->seeElement('.amendment279');
$I->dontSeeElement('.amendment280');

$I->click('.tagList .tagAll');

$I->seeElement('.motion118');
$I->seeElement('.motion2');
$I->seeElement('.amendment279');
$I->seeElement('.amendment280');


$I->wantTo('test as a regular admin');
$I->gotoConsultationHome();
$I->logout();
$I->loginAsStdAdmin();

$I->gotoMotion(true, 'Testing_proposed_changes-630');
$I->click('#sidebar .adminEdit a');
$I->see('Umwelt', '.tagList');
$I->dontSee('pseudotag', '.tagList');
$I->seeCheckboxIsChecked("//input[@name='tags[]'][@value='1']"); // Umwelt
$I->dontSeeCheckboxIsChecked("//input[@name='tags[]'][@value='2']");
