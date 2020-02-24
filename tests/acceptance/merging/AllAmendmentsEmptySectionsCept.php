<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('merge the amendments');

$I->gotoConsultationHome()->gotoMotionView(2);
$I->loginAsStdAdmin();
$I->click('.sidebarActions .mergeamendments a');
$I->click('.mergeAllRow .btn-primary');

sleep(1);

$I->see('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->executeJS('CKEDITOR.instances.sections_3_0_wysiwyg.setData("<p>Replaced Text</p>")');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->see('Replaced Text', '#sections_3_0_wysiwyg');

$I->wantTo('remove the text');
$I->executeJS('$(".section3 .removeSection input").prop("checked", true).trigger("change");');

// Save
$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->submitForm('.motionMergeForm', [], 'save');

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');


$I->wantTo('change it again');

$I->submitForm('#motionConfirmForm', [], 'modify');

$I->seeCheckboxIsChecked('.section3 .removeSection input');
$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->executeJS('$(".section3 .removeSection input").prop("checked", false).trigger("change");');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->see('Replaced Text', '#sections_3_0_wysiwyg');

$I->executeJS('$(".section3 .removeSection input").prop("checked", true).trigger("change");');
$I->dontSee('I-Düpferl-Reita', '#sections_3_0_wysiwyg');
$I->dontSee('Replaced Text', '#sections_3_0_wysiwyg');

$I->executeJS('$(".none").remove();'); // for some reason necessary...
$I->executeJS('$("#draftSavingPanel").remove();'); // for some reason necessary...
$I->wait(1);

$I->submitForm('.motionMergeForm', [], 'save');

$I->dontSee('I-Düpferl-Reita');
$I->dontSee('Replaced Text');

$I->submitForm('#motionConfirmForm', [], 'save');
