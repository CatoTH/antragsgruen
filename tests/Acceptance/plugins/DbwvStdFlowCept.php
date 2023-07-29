<?php

/** @var \Codeception\Scenario $scenario */

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->populateDBDataDbwv();


$I->wantTo('Antragsberechtigte: Antrag anlegen');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->loginAsDbwvTestUser('lv-sued-antragsberechtigt-0');
$I->see('Antragsberechtigte', '#userLoginPanel');
$I->see('Antrag stellen', '.btnCreateMotion');
$I->dontSeeElement('.myMotionList');
$I->click('.btnCreateMotion');

$I->fillField(['name' => 'sections[1]'], 'Testantrag');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData("<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.</p><p>Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>");');
$I->executeJS('CKEDITOR.instances.sections_3_wysiwyg.setData("<p>Es führt kein Weg an Lorem impsum vorbei</p>");');
$I->executeJS('$("#resolutionDate").val("23.05.2020")');
$I->submitForm('#motionEditForm', [], 'save');
$I->see('Organisation-0 (dort beschlossen am: 23.05.2020)');
$I->submitForm('#motionConfirmForm', [], 'confirm');
$I->see('Du hast den Antrag eingereicht.');
$I->submitForm('#motionConfirmedForm', [], '');
$I->see('Testantrag', '.myMotionList');
$I->logout();


$I->wantTo('AL Recht: Sachgebiet zuordnen');
$I->loginAsDbwvTestUser('al-recht');
$I->see('AL Recht', '#userLoginPanel');
$I->see('To Do (1)', '#adminTodo');

// AL Recht does not have extended permissions
$I->click('#motionListLink');
$I->dontSeeElement('.adminMotionListActions');
$I->dontSeeElement('.actionCol');
$I->click('.motion1 .prefixCol a');
$I->seeElement('#dbwv_main_tagSelect');
$I->executeJS('document.getElementById("dbwv_main_tagSelect").value = 47');
$I->trigerChangeJS('#dbwv_main_tagSelect');
$I->see('Gespeichert', '.alert-success');
$I->dontSee('To Do (1)', '#adminTodo');
$I->logout();


$I->wantTo('Referat III: Themengebiet/Nummer vergeben + Redaktionelle Änderung');
$I->loginAsDbwvTestUser('lv-sued-referat-iii');
$I->see('To Do (1)', '#adminTodo');
$I->seeElement('#dbwv_step1_subtagSelect');
$I->dontSeeElement('#dbwv_step1_subtagNew');
$I->executeJS('document.getElementById("dbwv_step1_subtagSelect").value = "new"');
$I->trigerChangeJS('#dbwv_step1_subtagSelect');
$I->seeElement('#dbwv_step1_subtagNew');
$I->fillField('#dbwv_step1_subtagNew', 'Gehalt');
$I->seeInField('#dbwv_step1_prefix', 'III/01');
$I->checkOption('#dbwv_step1_textchanges');
$I->submitForm('#dbwv_step1_assign_number', [], '');

$I->see('Antrag bearbeiten', 'h1');
$I->executeJS('CKEDITOR.instances.sections_2_wysiwyg.setData(CKEDITOR.instances.sections_2_wysiwyg.getData() + "<p>Third paragraph</p>")');
$I->submitForm('#motionEditForm', [], 'save');
$I->submitForm('#motionConfirmedForm', [], '');
$I->see('V2', '.motionHistory .currentVersion');
$I->clickJS('.motionHistory .historyOpener button');
$I->see('V1', '.motionHistory .otherVersion');
$I->click('.motionHistory .currentVersion .changesLink a');
$I->see('Third paragraph', '.inserted');
$I->dontSee('To Do (1)', '#adminTodo');
$I->logout();


$I->wantTo('Büroleitung: Antrag freischalten');
$I->loginAsDbwvTestUser('lv-sued-bueroleitung');
$I->gotoConsultationHome(true, 'std', 'lv-sued');
$I->dontSee('Testantrag');
$I->see('To Do (1)', '#adminTodo');
$I->click('#adminTodo');
$I->see('Antrag freischalten', '.motionScreen2');
$I->click('.motionScreen2 a');
$I->see('V2, Eingereicht (geprüft, unveröffentlicht)', '.motion2');
$I->see('To Do: Antrag freischalten', '.motion2');
$I->clickJS('.adminMotionListActions .markAll');
$I->submitForm('.motionListForm', [], 'screen');
$I->see('V2, Eingereicht', '.motion2');
$I->dontSee('To Do (1)', '#adminTodo');
$I->see('Testantrag');
$I->logout();


