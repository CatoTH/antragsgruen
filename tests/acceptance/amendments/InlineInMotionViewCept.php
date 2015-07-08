<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoStdConsultationHome()->gotoMotionView(2);

$I->wantTo('check that Ä2 is working correctly');
$I->dontSee('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');
$I->executeJS('$("#section_2_1").find("ul.bookmarks .amendment3").mouseover()');
$I->see('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');
$I->executeJS('$("#section_2_1").find("ul.bookmarks .amendment3").mouseout()');
$I->dontSee('Neuer Punkt');
$I->see('Auffi Gamsbart nimma');

$I->see('Woibbadinga noch da Giasinga');
$I->dontSee('Woibbadinga noch da Giasinga', '.deleted');
$I->executeJS('$("#section_2_4").find("ul.bookmarks .amendment").mouseover()');
$I->see('Woibbadinga noch da Giasinga', '.deleted');
$I->executeJS('$("#section_2_4").find("ul.bookmarks .amendment").mouseout()');
$I->dontSee('Woibbadinga noch da Giasinga', '.deleted');
