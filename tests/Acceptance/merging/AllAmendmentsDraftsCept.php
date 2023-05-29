<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSeeElement('.motionDataTable .mergingDraft');

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->dontSeeElement('.draftExistsAlert .btn');
$I->see('Einpflegen beginnen');
$I->checkOption('#markAmendment3');
$I->checkOption('#markAmendment1');
$I->checkOption('#markAmendment270');
$I->click('.mergeAllRow .btn-primary');
$I->see('annehmen oder ablehnen');

$I->wait(1);

// Reject "Neuer Punkt"
$I->executeJS('$("[data-cid=2] .appendHint").trigger("mouseover"); $("button.reject").click();');

$I->seeElement('#draftSavingPanel');
$I->dontSeeElement('#draftSavingPanel .publicLink');
$I->dontSeeCheckboxIsChecked('#draftSavingPanel input[name=public]');

$I->wait(1);
$I->wantTo('enable public drafts');
$I->executeJS('$("#draftSavingPanel input[name=public]").prop("checked", true).change();');
$I->wait(1);
$I->seeElement('#draftSavingPanel .publicLink');

// Prevent the alert from disturbing the window
$I->executeJS(' $(window).unbind("beforeunload");');

$I->logout();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->seeElement('.motionDataTable .mergingDraft');
$I->click('.motionDataTable .mergingDraft a');

$I->see('Dies ist kein beschlossener Antrag', '.alert');
$I->seeElement('#updateBtn');
$I->see('Neue Zeile', '.ice-ins');
$I->dontSee('Neuer Punkt', '.ice-ins');

$I->wantTo('see the info windows');
$I->dontSeeElement('.popover-amendment-ajax');
$I->click("//*[@id=\"sections_2\"]/ul[2]/li"); // Neue Zeile, Ä3
$I->wait(1);
$I->seeElement('.popover-amendment-ajax');
$I->see('Tester', '.popover-amendment-ajax');

$I->click("//*[@id=\"sections_2\"]/p[3]/ins"); // Woibbadinga damischa, Ä2
$I->wait(1);
$I->seeElement('.popover-amendment-ajax');
$I->see('Testadmin', '.popover-amendment-ajax');

$I->wantTo('restore the draft');
$I->gotoConsultationHome()->gotoMotionView(2);

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->seeElement('.draftExistsAlert .btn');

$I->seeElement('.draftExistsAlert');
$I->click('.draftExistsAlert .btn-primary');

$I->wait(1);
$I->see('Neue Zeile', '.ice-ins');
$I->dontSee('Neuer Punkt', '.ice-ins');
$I->executeJS('$("[data-cid=1] .appendHint").first().trigger("mouseover"); $("button.accept").click();');
$I->see('Neue Zeile');
$I->dontSee('Neue Zeile', '.ice-ins');

$I->click('#draftSavingPanel .saveDraft');
$I->wait(1);

// Prevent the alert from disturbing the window
$I->executeJS(' $(window).unbind("beforeunload");');



$I->wantTo('restore the second draft');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.sidebarActions .mergeamendments a');
$I->seeElement('.draftExistsAlert .btn');

$I->seeElement('.draftExistsAlert');
$I->click('.draftExistsAlert .btn-primary');

$I->wait(1);

$I->see('Neue Zeile');
$I->dontSee('Neue Zeile', '.ice-ins');
$I->dontSee('Neuer Punkt');



$I->wantTo('begin anew');
// Prevent the alert from disturbing the window
$I->executeJS(' $(window).unbind("beforeunload");');
$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.sidebarActions .mergeamendments a');
$I->seeElement('.draftExistsAlert .btn');

$I->checkOption('#markAmendment3');
$I->checkOption('#markAmendment1');
$I->checkOption('#markAmendment270');

$I->click('button.discard');
$I->wait(1);
$I->see('Neuer Punkt', '.ice-ins');
$I->see('Neue Zeile', '.ice-ins');
