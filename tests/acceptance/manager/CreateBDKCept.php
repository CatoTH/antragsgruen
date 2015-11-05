<?php

use app\tests\_pages\ManagerStartPage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

ManagerStartPage::openBy($I);

$I->validateHTML();
$I->validatePa11y();

$I->wantTo('go to creation form');
$I->loginAsStdAdmin();
$I->seeElement('.siteCreateForm');
$I->submitForm('.siteCreateForm', [], '');


$I->wantTo('fill in the creation form');
$I->selectOption('input[name="SiteCreateForm[preset]"]', 3);
$I->click('#next-1');

$I->fillField('input[name="SiteCreateForm[title]"]', 'BDK 2');
$I->fillField('input[name="SiteCreateForm[subdomain]"]', 'bdk-2');
$I->seeCheckboxIsChecked('.hasComments');
$I->seeCheckboxIsChecked('.hasAmendments');
$I->dontSeeCheckboxIsChecked('.openNow');
$I->click('#next-2');

$I->fillField('textarea[name="SiteCreateForm[contact]"]', 'Ich selbst' . "\n" . 'Meine Adresse');
$I->selectOption('input[name="SiteCreateForm[isWillingToPay]"]', 1);

$I->submitForm('form.siteCreate', [], 'create');

$I->see('Die Veranstaltung wurde angelegt.');



$I->wantTo('open the consultation');
$I->submitForm('.createdForm', [], '');

$I->see('Hallo auf Antragsgrün');
$I->see('BDK 2', 'h1');


$I->wantTo('check that maintainance mode is on');
$I->logout();
$I->dontSee('Hallo auf Antragsgrün');
$I->dontSee('BDK 2', 'h1');
$I->see('Wartungsmodus', 'h1');


$I->loginAsWurzelwerkUser();
$I->see('Wartungsmodus', 'h1');
$I->click('.homeLinkLogo');
$I->see('Wartungsmodus', 'h1');
$I->logout();

$I->seeInPageSource('layout-gruenes-ci.css');



$I->wantTo('check the imprint');
$I->click('#legalLink');
$I->see('Ich selbst');
$I->see('Meine Adresse');
