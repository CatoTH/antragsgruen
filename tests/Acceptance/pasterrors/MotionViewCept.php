<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check ###FORCELINEBREAK### is not visible');

$I->gotoConsultationHome();
$I->gotoMotion(true, 3);
$I->see('Zitat 223');
$I->dontSee('###FORCELINEBREAK###');
