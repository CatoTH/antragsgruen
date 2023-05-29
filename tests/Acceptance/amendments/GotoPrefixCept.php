<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Open an amendment using the prefix shortcut');
$I->amOnUrl(str_replace(
    ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
    ['stdparteitag', 'std-parteitag', 'A2/Ä5'],
    AcceptanceTester::ABSOLUTE_URL_TEMPLATE
));
$I->see('und irgendw');


$I->amOnUrl(str_replace(
    ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
    ['laenderrat-to', 'laenderrat-to', 'Z-01-224-1'],
    AcceptanceTester::ABSOLUTE_URL_TEMPLATE
));
$I->see('Z-01-224-1', 'h1');
