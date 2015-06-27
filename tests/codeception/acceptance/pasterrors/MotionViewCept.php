<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$I = new AntragsgruenAcceptenceTester($scenario);
$I->populateDBData1();

$I->wantTo('check ###FORCELINEBREAK### is not visible');

$I->gotoStdConsultationHome();
$I->gotoMotion(true, 3);
$I->see('Zitat 223');
$I->dontSee('###FORCELINEBREAK###');
