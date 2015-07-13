<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();
$I->loginAndGotoStdAdminPage()->gotoMotionList()->gotoAmendmentEdit(1);
$I->submitForm('#amendmentUpdateForm', [], 'save');
$I->click('.sidebarActions .view');
$I->dontSeeElement('del');
$I->seeElement('ul.inserted');
