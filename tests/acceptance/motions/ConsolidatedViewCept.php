<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('test the consolidated view');
$page = \app\tests\_pages\ConsolidatedMotionViewPage::openStd($I);

$I->see('Kollidierender Änderungsantrag: Ä6 zu A2: O’zapft is!', '.collidingAmendment');
$I->see('Brezen', '.collidingAmendment ins');
$I->see('Brezen', '.collidingAmendment ins');
$I->see('Dahoam gelbe Rüam ...', '.collidingAmendment');
