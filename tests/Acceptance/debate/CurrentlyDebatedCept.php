<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

// The std-parteitag fixture ships with the "Currently Debated" feature enabled and an ongoing debate on
// motion A2 ("O’zapft is!"). Guests/regular users see the inline widget, admins the moderation widget.

$I->wantTo('see the currently debated motion as a guest');
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateInline .debatedItem', 5);
$I->see('O’zapft is!', '.currentDebateInline .debatedItem .title');
$I->dontSeeElement('.currentDebateAdmin'); // the moderation widget is only rendered for moderators


$I->wantTo('end the ongoing debate as an admin');
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->dontSeeElement('.currentDebateInline'); // admins get the moderation widget instead of the inline one
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->see('O’zapft is!', '.currentDebateAdmin .debatedItem .title');
$I->click('.currentDebateAdmin .debatedItem .stopDebateBtn');
$I->waitForText('Aktuell findet keine Debatte statt', 5, '.currentDebateAdmin');


$I->wantTo('start a debate over another motion');
$I->waitForElement('#debateAdminSelect-motion', 5);
$I->selectOption('#debateAdminSelect-motion', ['value' => '3']); // A3: Textformatierungen
$I->click('.currentDebateAdmin .selectRow-motion .rowButton button');
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);
$I->see('Textformatierungen', '.currentDebateAdmin .debatedItem .title');


$I->wantTo('confirm the debated motion is visible to a regular user');
$I->logout();
$I->loginAsStdUser();
$I->gotoConsultationHome();
$I->dontSeeElement('.currentDebateAdmin');
$I->see('Textformatierungen', '.currentDebateInline .debatedItem .title');
