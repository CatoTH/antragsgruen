<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('switch an amendment to full motion text mode');
$I->gotoAmendment(true, '321-o-zapft-is', 1);
$I->dontSee('Bavaria ipsum dolor');
$I->see('Oamoi a Maß', '.inserted');

$page = $I->loginAndGotoMotionList()->gotoAmendmentEdit(1);
$I->checkOption('#defaultViewModeFull');
$page->saveForm();

$I->gotoAmendment(true, '321-o-zapft-is', 1);
$I->see('Bavaria ipsum dolor');
$I->see('Oamoi a Maß', '.inserted');

$I->clickJS('#section_2 .dropdown-toggle');
$I->wait(0.1);
$I->seeElement('#section_2 .dropdown-menu li.selected .showFullText');
