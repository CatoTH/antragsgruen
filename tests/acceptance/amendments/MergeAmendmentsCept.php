<?

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->gotoConsultationHome()->gotoMotionView(2);
$I->dontSeeElement('.sidebarActions .mergeamendments');

$I->wantTo('merge the amendments');
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->see('annehmen oder ablehnen');
$I->see('kollidierende Änderungsanträge');
$I->see('Neue Zeile', 'ins.ice-cts');
$I->see('Neuer Punkt', 'ins.ice-cts');
$I->see('Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.', 'del.ice-cts');
$ret = $I->executeJS('return CKEDITOR.instances.sections_2_wysiwyg.plugins.lite.findPlugin(CKEDITOR.instances.sections_2_wysiwyg).countChanges();');
if ($ret < 10) {
    $I->fail('Number of changes: ' . $ret . ' (Should be: 16)');
}
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.plugins.lite.findPlugin(CKEDITOR.instances.sections_2_wysiwyg).acceptChange($("ins[data-cid=1]")[0]);');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.plugins.lite.findPlugin(CKEDITOR.instances.sections_2_wysiwyg).rejectChange($("ins[data-cid=2]")[0]);');
$I->dontSee('Neue Zeile', 'ins.ice-cts');
$I->dontSee('Neuer Punkt', 'ins.ice-cts');
$I->see('Neue Zeile');
$I->dontSee('Neuer Punkt');

$I->see('Alternatives Ende');
$I->click('#section_holder_2 .rejectAllChanges');
$I->seeBootboxDialog('Wirklich alle verbleibenden Änderungen dieses Textabschnitts ablehnen?');
$I->acceptBootboxConfirm();
$I->dontSee('Alternatives Ende');

// @TODO Set amendment status

$I->submitForm('.motionMergeForm', [], 'save');

$I->see('Überarbeitung kontrollieren', 'h1');
$I->see('Neue Zeile');
$I->dontSee('Neuer Punkt');
$I->dontSee('Alternatives Ende');

// @TODO Modify

$I->submitForm('#motionConfirmForm', [], 'confirm');

$I->see('Der Antrag wurde überarbeitet');
$I->submitForm('#motionConfirmedForm', [], '');


$I->wantTo('check if the modifications were made');
$I->see('A2neu', 'h1');
$I->see('Neue Zeile');
$I->dontSee('Neuer Punkt');
$I->dontSee('Alternatives Ende');
$I->see('A2:', '.replacesMotion');


$I->click('.replacesMotion a');
$I->see('Achtung: dies ist eine alte Fassung', '.motionReplayedBy.alert-danger');
