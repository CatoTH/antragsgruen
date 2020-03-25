<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

var_dump($I->apiSetAmendmentStatus('stdparteitag', 'std-parteitag', 270, -1));
die();
