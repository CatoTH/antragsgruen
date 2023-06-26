<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');

$I->dontSeeElement('.amendment136');
$I->dontSeeElement('.motionLink50');
$I->seeElement('.motionLink47');
$I->dontSeeElement('.motionLink52'); // Draft
$I->dontSeeElement('.amendment58');
