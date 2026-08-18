<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$tabDebated = '.currentDebateAdmin .debateAdminTabs .tab:nth-of-type(1)';

// The std-parteitag fixture ships with an ongoing debate on motion A2 ("O’zapft is!"), which has no
// speaking list and no voting yet - so the debated tab offers the "Activate" / "Create" variants.
$I->gotoConsultationHome();
$I->loginAsStdAdmin();
$I->gotoConsultationHome();
$I->waitForElement('.currentDebateAdmin .debatedItem', 5);


$I->wantTo('activate the speaking list directly from the debated tab');
$I->see('Redeliste aktivieren', '.currentDebateAdmin .manageSpeechBtn'); // no active list yet
$I->click('.currentDebateAdmin .manageSpeechBtn');
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8); // activated + switched to the tab

$I->wantTo('see the speaking-list button switch to "manage" once a list is active');
$I->click($tabDebated);
$I->waitForText('Redeliste verwalten', 5, '.currentDebateAdmin .manageSpeechBtn');
$I->click('.currentDebateAdmin .manageSpeechBtn'); // now just switches to the tab
$I->waitForElement('.currentDebateAdmin .speechTab .speechAdmin', 8);


$I->wantTo('create the voting directly from the debated tab');
$I->click($tabDebated);
$I->waitForElement('.currentDebateAdmin .manageVotingBtn', 5);
$I->see('Abstimmung anlegen', '.currentDebateAdmin .manageVotingBtn'); // no voting associated yet
$I->click('.currentDebateAdmin .manageVotingBtn');
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8); // created + switched to the tab
$I->see('In Vorbereitung', '.votingTab .votingCard .votingCardStatus');

$I->wantTo('see the voting button switch to "manage" once a voting is associated');
$I->click($tabDebated);
$I->waitForText('Abstimmung verwalten', 5, '.currentDebateAdmin .manageVotingBtn');
$I->click('.currentDebateAdmin .manageVotingBtn'); // now just switches to the tab
$I->waitForElement('.currentDebateAdmin .votingTab .votingCard', 8);
