<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome();

$I->seeElement('ol.breadcrumb');

$I->wantTo('disable the breadcrumb');
$I->loginAsStdAdmin();
$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->uncheckOption('#showBreadcrumbs');
$page->saveForm();

$I->gotoConsultationHome();
$I->dontSeeElement('ol.breadcrumb');

$I->wantTo('enable it again');

$page = $I->gotoStdAdminPage()->gotoAppearance();
$I->checkOption('#showBreadcrumbs');
$page->saveForm();

$I->gotoConsultationHome();
$I->seeElement('ol.breadcrumb');
