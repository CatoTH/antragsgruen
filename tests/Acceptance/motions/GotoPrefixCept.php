<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('Open a motion using the prefix shortcut');
$I->amOnUrl(str_replace(
    ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
    ['stdparteitag', 'std-parteitag', 'A2'],
    AcceptanceTester::ABSOLUTE_URL_TEMPLATE
));
$I->see('Wui helfgod Wiesn');



$I->amOnUrl(str_replace(
    ['{SUBDOMAIN}', '{CONSULTATION}', '{PATH}'],
    ['laenderrat-to', 'laenderrat-to', 'F-01'],
    AcceptanceTester::ABSOLUTE_URL_TEMPLATE
));
$I->see('F-01', 'h1');
