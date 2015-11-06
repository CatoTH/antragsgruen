<?php

use app\tests\_pages\ManagerStartPage;

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

ManagerStartPage::openBy($I);

$I->wantTo('go to creation form');
$I->loginAsStdAdmin();
$I->seeElement('.siteCreateForm');
$I->submitForm('.siteCreateForm', [], '');


$I->wantTo('fill in the creation form');
$I->selectOption('input[name="SiteCreateForm[preset]"]', 2);
$I->click('#next-1');

$I->fillField('input[name="SiteCreateForm[organization]"]', 'KV Neuland');
$I->fillField('input[name="SiteCreateForm[title]"]', 'Internet-Konferenz');
$I->fillField('input[name="SiteCreateForm[subdomain]"]', 'neuland');
$I->seeCheckboxIsChecked('.hasComments');
$I->seeCheckboxIsChecked('.hasAmendments');
$I->seeCheckboxIsChecked('.openNow');
$I->click('#next-2');

$I->fillField('textarea[name="SiteCreateForm[contact]"]', 'Ich selbst' . "\n" . 'Meine Adresse');
$I->selectOption('input[name="SiteCreateForm[isWillingToPay]"]', 1);

$I->submitForm('form.siteCreate', [], 'create');

$I->see('Die Veranstaltung wurde angelegt.');



$I->wantTo('open the consultation');
$I->submitForm('.createdForm', [], '');

$I->see('Hallo auf Antragsgrün');
$I->see('Internet-Konferenz', 'h1');


$I->wantTo('check that maintainance mode is off');
$I->logout();
$I->see('Hallo auf Antragsgrün');
$I->see('Internet-Konferenz', 'h1');
$I->dontSee('Wartungsmodus', 'h1');


$I->wantTo('check the imprint');
$I->click('#legalLink');
$I->see('Ich selbst');
$I->see('Meine Adresse');


$I->wantTo('check it is visible on the manager page');
ManagerStartPage::openBy($I);
$I->see('KV Neuland', '#sidebar');
$I->see('Internet-Konferenz', '#sidebar');
