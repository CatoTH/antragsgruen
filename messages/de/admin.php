<?php

return [
    'bread_admin'             => 'Administration',
    'bread_list'              => 'Antragsliste',
    'saved'                   => 'Gespeichert.',
    'amend_deleted'           => 'Der Änderungsantrag wurde gelöscht.',
    'amend_screened'          => 'Der Änderungsantrag wurde freigeschaltet.',
    'amend_prefix_collission' => 'Das angegebene Antragskürzel wird bereits von einem anderen Änderungsantrag verwendet.',
    'list_head_title'         => 'Liste: Anträge, Änderungsanträge',
    'list_action'             => 'Aktion',
    'list_export'             => 'Export',
    'list_tag'                => 'Thema',
    'list_initiators'         => 'InitiatorInnen',
    'list_status'             => 'Status',
    'list_title'              => 'Titel',
    'list_prefix'             => 'Antragsnr.',
    'list_type'               => 'Typ',
    'list_motion_short'       => 'A',
    'list_amend_short'        => 'ÄA',
    'list_search_do'          => 'Suchen',
    'list_delete'             => 'Löschen',
    'list_unscreen'           => 'Ent-Freischalten',
    'list_screen'             => 'Freischalten',
    'list_all'                => 'Alle',
    'list_none'               => 'Keines',
    'list_marked'             => 'Markierte',
    'list_template_amendment' => 'Neuer Änderungsantrag auf dieser Basis',
    'list_template_motion'    => 'Neuer Antrag auf dieser Basis',
    'list_confirm_del_motion' => 'Diesen Antrag wirklich löschen?',
    'list_confirm_del_amend'  => 'Diesen Änderungsantrag wirklich löschen?',
    'cons_email_from'         => 'Absender-Name',
    'cons_email_from_place'   => 'Standard: "%NAME%"',
    'siteacc_bread'           => 'Zugang',
    'siteacc_title'           => 'Zugang zur Seite',
    'siteacc_policywarning'   => '<h3>Hinweis:</h3>
Die BenutzerInnenverwaltung unten kommt erst dann voll zur Geltung, wenn die Leserechte oder die Rechte zum Anlegen
 von Anträgen, Änderungsanträgen, Kommentaren etc. auf "Nur eingeloggte BenutzerInnen" gestellt werden. Aktuell ist
 das nicht der Fall.<br>
 <br>
 Falls die nur für unten eingetragene BenutzerInnen <em>sichtbar</em> sein soll, wähle die Einstellung gleich unterhalb
 dieses Hinweises aus. Falls die Seite für alle einsehbar sein soll, aber nur eingetragene BenutzerInnen
 Anträge etc. stellen können sollen, kannst du das hiermit automatisch einstellen:',
    'siteacc_policy_login'    => 'Auf BenutzerInnen einschränken',
    'siteacc_forcelogin'      => 'Nur eingeloggte BenutzerInnen dürfen zugreifen (inkl. <em>lesen</em>)',
    'siteacc_managedusers'    => 'Nur ausgewählten BenutzerInnen das Login erlauben <small class="showManagedUsers">(siehe unten)</small>',
    'siteacc_logins'          => 'Folgende Login-Varianten sind möglich',
    'siteacc_useraccounts'    => 'Standard-Antragsgrün-Accounts <small>(alle mit gültiger E-Mail-Adresse)</small>',
    'siteacc_ww'              => 'Wurzelwerk <small>(alle mit Wurzelwerk-Zugang)</small>',
    'siteacc_otherlogins'     => 'Sonstige Methoden <small>(OpenID, evtl. zufünftig auch Login per Facebook / Twitter)</small>',
    'siteacc_admins_title'    => 'Administrator_Innen der Reihe',
    'siteacc_admins_add'      => 'Neu eintragen',
    'siteacc_add_ww'          => 'Wurzelwerk-Name',
    'siteacc_add_email'       => 'E-Mail-Adresse',
    'siteacc_add_name_title'  => 'Wurzelwerk-BenutzerInnenname / E-Mail-Adresse',
    'siteacc_add_name_place'  => 'Name',
    'siteacc_add_btn'         => 'Hinzufügen',
    'siteacc_accounts_title'  => 'Benutzer_Innen-Accounts',
    'siteacc_email_text_pre'  => 'Hallo,

wir haben dir soeben Zugang zu unserer Antragsgrün-Seite eingerichtet, ' .
        'auf der du über unseren Entwurf mitdiskutieren kannst.
Hier ist der Zugang:

%LINK%
%ACCOUNT%

Liebe Grüße,
  Das Antragsgrün-Team',
    'siteacc_acc_expl_mail'   => '<h3>Erklärung:</h3>
Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll,
kannst du hier die BenutzerInnen anlegen, die Zugriff haben sollen.<br>
<br>
Um BenutzerInnen anzulegen, gib weiter unten die E-Mail-Adressen der Mitglieder ein.
Diese Mitglieder bekommen daraufhin eine Benachrichtigungs-E-Mail zugesandt.<br>
Falls sie noch keinen eigenen Zugang auf Antragsgrün hatten, wird automatisch einer eingerichtet
und an der Stelle von <strong>%ACCOUNT%</strong> erscheinen die Zugangsdaten
(ansonsten verschwindet das %ACCOUNT% ersatzlos).<br>
<strong>%LINK%</strong> wird immer durch einen Link auf die Antragsgrün-Seite ersetzt.',
    'siteacc_acc_expl_nomail' => '<h3>Erklärung:</h3>
Wenn die Antragsgrün-Seite oder die Antrags-/Kommentier-Funktion nur für bestimmte Mitglieder zugänglich sein soll,
kannst du hier die BenutzerInnen anlegen, die Zugriff haben sollen.<br>
<br>
Um BenutzerInnen anzulegen, gib weiter unten die E-Mail-Adressen, die Namen und die Passwörter der Mitglieder ein.
Da <strong>kein E-Mail-Versand</strong> eingerichtet ist, musst du die <strong>Passwörter</strong> hier selbst erzeugen, im Klartext eingeben und selbst an die NutzerInnen schicken.<br><br>' .
        'Aus <strong>Datenschutzgründen</strong> wäre empfehlenswerter, zunächst den E-Mail-Versand einzurichten, damit Antragsgrün automatisch Passwörter erzeugen und direkt an die NutzerInnen schicken kann.',
    'siteacc_existing_users'  => 'Bereits eingetragene Benutzer_Innen',
    'siteacc_user_name'       => 'Name',
    'siteacc_user_login'      => 'Login',
    'siteacc_user_read'       => 'Lesen',
    'siteacc_user_write'      => 'Anlegen',
    'siteacc_perm_read'       => 'Leserechte',
    'siteacc_perm_write'      => 'Schreibrechte',
    'siteacc_new_users'       => 'Benutzer_Innen eintragen',
    'siteacc_new_emails'      => 'E-Mail-Adressen:<br>
                <small>(Genau eine E-Mail-Adresse pro Zeile)</small>',
    'siteacc_new_pass'        => 'Passwörter:<br>
                <small>(Exakte Zuordnung zu den Zeilen links!)</small>',
    'siteacc_new_names'       => 'Namen der BenutzerInnen:<br>
                <small>(Exakte Zuordnung zu den Zeilen links!)</small>',
    'siteacc_new_text'        => 'Text der E-Mail',
    'siteacc_new_do'          => 'Berechtigen / Anlegen',
    'siteacc_admin_add_done'  => '%username% hat nun auch Admin-Rechte.',
    'siteacc_admin_add_had'   => '%username% hatte bereits Admin-Rechte.',
    'siteacc_admin_del_done'  => 'Die Admin-Rechte wurden entzogen.',
    'siteacc_admin_del_notf'  => 'Es gibt keinen Zugang mit diesem Namen',
    'siteacc_mail_yourdata'   => "Du kannst dich mit folgenden Angaben einloggen:\nBenutzerInnenname: %EMAIL%\n" .
        "Passwort: %PASSWORD%",
    'siteacc_mail_youracc'    => 'Du kannst dich mit deinem BenutzerInnenname %EMAIL% einloggen.',
    'sitacc_admmail_subj'     => 'Antragsgrün-Administration',
    'sitacc_admmail_body'     => "Hallo!\n\nDu hast eben Admin-Zugang zu folgender Antragsgrün-Seite bekommen: %LINK%\n\n" .
        "%ACCOUNT%\n\nLiebe Grüße,\n  Das Antragsgrün-Team",
    'siteacc_err_linenumber'  => 'Die Zahl der E-Mail-Adressen und der Namen stimmt nicht überein',
    'siteacc_err_occ'         => 'Es sind Fehler aufgetreten',
    'siteacc_user_had'        => 'Folgende BenutzerInnen hatten bereits Zugriff',
    'siteacc_user_added_1'    => '%NUM% BenutzerIn wurde eingetragen.',
    'siteacc_user_added_x'    => '%NUM% BenutzerInnen wurden eingetragen.',
    'siteacc_user_added_0'    => 'Es wurde niemand eingetragen.',
    'siteacc_user_saved'      => 'Die Berechtigungen wurden gespeichert.',
    'siteacc_user_restr_done' => 'Nur noch eingetragene BenutzerInnen können Einträge erstellen.',
];
