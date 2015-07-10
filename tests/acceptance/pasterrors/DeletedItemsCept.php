<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome(true, '1laenderrat2015', '1laenderrat2015');

$I->dontSeeElement('.amendment136');
$I->dontSeeElement('.motionLink50');
$I->seeElement('.motionLink47');
