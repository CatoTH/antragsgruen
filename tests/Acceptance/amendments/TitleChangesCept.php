<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('see amendments affecting the title of a motion');
$I->gotoConsultationHome();

$I->gotoMotion(true, '321-o-zapft-is');
$I->seeElement('.sectionType0 .amendment1');
$I->seeElement('.sectionType0 .amendment274');
$I->dontSeeElement('.sectionType0 .amendment276');

$I->gotoMotion(true, '123-textformatierungen');
$I->dontSeeElement('.sectionType0');
