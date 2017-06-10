<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');

$I->see('Einpflegen beginnen');
$I->uncheckOption('.toMergeAmendments .amendment3');

$I->click('.mergeAllRow .btn-primary');
$I->see('annehmen oder ablehnen');

$I->wait(1);

$I->see('Neue Zeile', '.ice-ins');
$I->dontSee('Neuer Punkt');


$I->wantTo('try another combination');

// Prevent the alert from disturbing the window
$I->executeJS(' $(window).unbind("beforeunload");');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->click('.sidebarActions .mergeamendments a');
$I->uncheckOption('.toMergeAmendments .amendment270');
$I->click('.mergeAllRow .btn-primary');

$I->see('annehmen oder ablehnen');

$I->wait(1);

$I->dontSee('Neue Zeile');
$I->see('Neuer Punkt', '.ice-ins');
