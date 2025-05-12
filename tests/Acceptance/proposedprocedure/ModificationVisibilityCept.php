<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('see my amendments, but not the modified changes');
$I->gotoConsultationHome();

// Remove relicts from previous test cases
$I->executeJS('for (let key in localStorage) localStorage.removeItem(key);');

$I->loginAsStdUser();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 281);
$I->seeElement('#sidebar .withdraw');
$I->dontSee('brains');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 283);
$I->seeElement('#sidebar .withdraw');
$I->dontSee('brains');


$I->wantTo('make the changes visible as admin');
$I->logout();
$I->loginAsProposalAdmin();

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 281);
$I->see('brains');
$I->see('Verfahrensvorschlag:', 'h2');
$I->dontSeeElement('#proposedChanges');
$I->clickJS('.proposedChangesOpener button');
$I->wait(0.3);
$I->seeElement('#proposedChanges');
$I->click('#proposedChanges .notifyProposer');
$I->seeElement('.notifyProposerSection');
$I->click('.notifyProposerSection button');
$I->wait(0.5);
$I->see('Noch keine Bestätigung', '.notificationStatus');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 283);
$I->see('brains');
$I->see('Verfahrensvorschlag zu Ä3:', 'h2');
$I->seeElement('#proposedChanges');
$I->click('#proposedChanges .notifyProposer');
$I->seeElement('.notifyProposerSection');
$I->click('.notifyProposerSection button');
$I->wait(0.5);
$I->see('Noch keine Bestätigung', '.notificationStatus');


$I->wantTo('not see the changes logged out');
$I->logout();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 281);
$I->dontSee('brains');
$I->dontSee('Verfahrensvorschlag:', 'h2');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 283);
$I->dontSee('brains');
$I->dontSee('Verfahrensvorschlag zu Ä3:', 'h2');


$I->wantTo('see the changes as initiator');
$I->loginAsStdUser();
$I->gotoAmendment(true, 'Testing_proposed_changes-630', 281);
$I->see('brains');
$I->see('Verfahrensvorschlag:', 'h2');

$I->gotoAmendment(true, 'Testing_proposed_changes-630', 283);
$I->see('brains');
$I->see('Verfahrensvorschlag zu Ä3:', 'h2');
