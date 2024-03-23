<?php

/** @var \Codeception\Scenario $scenario */
use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('activate line length');
$I->loginAndGotoStdAdminPage()->gotoMotionTypes(1);

$I->dontSeeElement('.section1 .maxLenInput');
$I->checkOption('.section1 .maxLenSet');
$I->seeElement('.section1 .maxLenInput');
$I->fillField('.section1 .maxLenInput input', 20);
$I->checkOption('.section2 .maxLenSet');
$I->fillField('.section2 .maxLenInput input', 100);
$I->checkOption('.section3 .maxLenSet');
$I->fillField('.section3 .maxLenInput input', 150);
$I->checkOption('.section3 .maxLenSoftCheckbox');
$I->submitForm('.adminTypeForm', [], 'save');

$I->see('Gespeichert.');
$I->seeElement('.section1 .maxLenInput');
$I->seeCheckboxIsChecked('.section1 .maxLenSet');
$I->dontSeeCheckboxIsChecked('.section1 .maxLenSoftCheckbox');
$I->seeInField('.section1 .maxLenInput input', '20');
$I->seeCheckboxIsChecked('.section2 .maxLenSet');
$I->dontSeeCheckboxIsChecked('.section2 .maxLenSoftCheckbox');
$I->seeInField('.section2 .maxLenInput input', '100');
$I->seeCheckboxIsChecked('.section3 .maxLenSet');
$I->seeCheckboxIsChecked('.section3 .maxLenSoftCheckbox');
$I->seeInField('.section3 .maxLenInput input', '150');

$I->wantTo('create a motion');

$I->gotoConsultationHome()->gotoMotionCreatePage();
$I->wait(1);

$I->dontSee('Der Text ist zu lang');
$I->see('Max. 20 Zeichen (Aktuell: 0)');
$I->see('Max. 100 Zeichen (Aktuell: 0)');
$I->see('Max. 150 Zeichen (Aktuell: 0)');

$I->fillField('#sections_1', '12345');
$I->dontSee('Der Text ist zu lang');
$I->see('Max. 20 Zeichen (Aktuell: 5)');
$I->fillField('#sections_1', str_repeat('x', 21));
$I->see('Der Text ist zu lang');
$I->see('Max. 20 Zeichen (Aktuell: 21)');
$disabled = $I->executeJS('return ($(".motionEditForm button[type=submit]").prop("disabled") ? "yes" : "no");');
if ($disabled!=='yes') {
    $I->fail('submitting still allowed');
}

$I->fillField('#sections_1', str_repeat('x', 20));
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("'. str_repeat('x', 101) . '");');
$I->see('Der Text ist zu lang');
$I->see('Max. 100 Zeichen (Aktuell: 101)');
$disabled = $I->executeJS('return ($(".motionEditForm button[type=submit]").prop("disabled") ? "yes" : "no");');
if ($disabled!=='yes') {
    $I->fail('submitting still allowed');
}

$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("'. str_repeat('x', 100) . '");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("'. str_repeat('x', 151) . '");');
$I->see('Max. 150 Zeichen (Aktuell: 151)');
$I->see('Der Text ist zu lang');
$disabled = $I->executeJS('return ($(".motionEditForm button[type=submit]").prop("disabled") ? "yes" : "no");');
if ($disabled!=='no') {
    $I->fail('submitting not allowed');
}

$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("'. str_repeat('x', 150) . '");');
$I->dontSee('Der Text ist zu lang');
