<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$page = $I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);
$I->seeCheckboxIsChecked('.section2 .lineNumbers');
$I->uncheckOption('.section2 .lineNumbers');
$page->saveForm();
$I->dontSeeCheckboxIsChecked('.section2 .lineNumbers');

$I->gotoMotion();
$I->dontSee('###LINENUMBER###');
