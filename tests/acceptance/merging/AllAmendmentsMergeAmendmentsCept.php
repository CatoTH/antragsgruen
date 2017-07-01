<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSeeElement('.sidebarActions .mergeamendments');

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->see('Einpflegen beginnen');
$I->click('.mergeAllRow .btn-primary');
$I->see('annehmen oder ablehnen');
$I->see('kollidierende Änderungsanträge');
$I->see('Neuer Punkt', '.ice-ins');
$I->see('Oamoi a Maß', '.ice-ins');
$I->see('Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.', '.ice-del');

$I->see('Woibbadinga damischa', '#section_holder_4 .ice-del');
$I->see('Schooe', '#section_holder_4 .ice-ins');

$cid0 = $I->executeJS('return $("[data-cid=0]").length;');
$cid1 = $I->executeJS('return $("[data-cid=1]").length;');
$cid2 = $I->executeJS('return $("[data-cid=2]").length;');
$cid3 = $I->executeJS('return $("[data-cid=3]").length;');
if ($cid0 != 1 || $cid1 != 1 || $cid2 != 1 || $cid3 != 1) {
    $I->fail('wrong number of cid\'s: ' . $cid0 . ' / ' . $cid1 . ' / ' . $cid2 . ' / ' . $cid3);
}

// Neuer Punkt einfügen
$I->executeJS('$("[data-cid=4] .appendHint").trigger("mouseover"); $("button.reject").click();');
// Oamoi a Map einfügen
$I->executeJS('$("[data-cid=6] .appendHint").trigger("mouseover"); $("button.accept").click();');
// Woibbadinga damischa raus, Schooe rein
$I->executeJS('$("[data-cid=17].appendHint").first().trigger("mouseover"); $("button.accept").click();');
$I->wait(1);

$I->dontSee('Neuer Punkt', '.ice-ins');
$I->dontSee('Oamoi a Maß', '.ice-ins');
$I->dontSee('Neuer Punkt');
$I->see('Oamoi a Maß');

$I->dontSee('Woibbadinga damischa', '#section_holder_4 .ice-del');
$I->dontSee('Schooe', '#section_holder_4 .ice-ins');
$I->see('Schooe', '#section_holder_4');

$I->see('Something');
$I->executeJS('$("#section_holder_2 .rejectAllChanges").click();');
$I->dontSee('Something');

// @TODO Set amendment status

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);
$I->submitForm('.motionMergeForm', [], 'save');

$I->see('Überarbeitung kontrollieren', 'h1');
$I->see('Oamoi a Maß');
$I->dontSee('Neuer Punkt');
$I->dontSee('Alternatives Ende');

// @TODO Modify

$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('check if the modifications were made');
$I->see('A2neu', 'h1');
$I->see('Oamoi a Maß');
$I->see('Schooe');
$I->dontSee('Neuer Punkt');
$I->dontSee('Alternatives Ende');
$I->see('A2:', '.replacesMotion');


$I->click('.replacesMotion a');
$I->see('Achtung: dies ist eine alte Fassung', '.motionReplayedBy.alert-danger');
