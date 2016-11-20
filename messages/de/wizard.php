<?php

return [
    'title' => 'Antragsgrün-Seite anlegen',

    'step_purpose'    => 'Einsatzzweck',
    'step_motions'    => 'Anträge',
    'step_amendments' => 'Änderungsanträge',
    'step_special'    => 'Sonderfälle',
    'step_site'       => 'Organisatorisches',

    'next'   => 'Weiter',
    'prev'   => 'Zurück',
    'finish' => 'Fertig / Seite anlegen',

    'purpose_title'          => 'Was soll diskutiert werden?',
    'purpose_desc'           => 'Das wirkt sich ausschließlich auf das &quot;Wording&quot; aus.',
    'purpose_motions'        => 'Anträge',
    'purpose_manifesto'      => 'Programm',
    'purpose_manifesto_desc' => '(Wahl-/Partei-)',

    'single_mot_title' => 'Werden mehrere Anträge diskutiert?',
    'single_man_title' => 'Gibt es mehrere Kapitel?',
    'single_mot_desc'  => 'Bei nur einem entfällt die Antragsübersicht',
    'single_man_desc'  => 'Bei nur einem entfällt die Übersichtsseite',
    'single_one'       => 'Nur einer',
    'single_multi'     => 'Mehrere',

    'motwho_mot_title' => 'Wer darf Anträge einreichen?',
    'motwho_man_title' => 'Wer darf Kapitel einreichen?',
    'motwho_admins'    => 'Admins',
    'motwho_loggedin'  => 'Registrierte Benutzer*innen',
    'motwho_all'       => 'Alle',

    'amendwho_title'    => 'Wer darf Änderungsanträge einreichen?',
    'amendwho_admins'   => 'Admins',
    'amendwho_loggedin' => 'Registrierte Benutzer*innen',
    'amendwho_all'      => 'Alle',

    'motdead_title'   => 'Gibt es einen Antragsschluss?',
    'motdead_desc'    => 'Der Zeitpunkt, bis zu dem Anträge eingereicht werden können',
    'motdead_no'      => 'Nein',
    'motdead_yes'     => 'Ja:',
    'amenddead_title' => 'Antragsschluss für Änderungsanträge?',
    'amenddead_desc'  => 'Der Zeitpunkt, bis zu dem Änderungsanträge eingereicht werden können',
    'amenddead_no'    => 'Nein',
    'amenddead_yes'   => 'Ja:',

    'screening_mot_title'   => 'Vorabkontrolle neuer Anträge?',
    'screening_man_title'   => 'Vorabkontrolle neuer Kapitel?',
    'screening_amend_title' => 'Vorabkontrolle von Änderungsanträgen?',
    'screening_desc'        => 'Soll eine Freischaltung durch einen Admin erfolgen, bevor sie öffentlich sichtbar sind?',
    'screening_yes'         => 'Ja, Vorabkontrolle',
    'screening_no'          => 'Keine Vorabkontrolle',

    'supporters_title' => 'Unterstützer*innen abfragen?',
    'supporters_desc'  => 'Muss die Antragsteller*in beim Eintragen von Anträgen Unterstützer*innen benennen?',
    'supporters_yes'   => 'Ja, mindestens:',
    'supporters_no'    => 'Nein',

    'amend_title' => 'Sind Änderungsanträge zugelassen?',
    'amend_no'    => 'Nein',
    'amend_yes'   => 'Ja',

    'amend_singlepara_title'  => 'Dürfen mehrere Textstellen geändert werden?',
    'amend_singlepara_desc'   => 'Änderungsanträge können auch auf einen Absatz beschränkt werden',
    'amend_singlepara_single' => 'Nur eine Textstelle',
    'amend_singlepara_multi'  => 'Mehrere Textstellen',

    'comments_title' => 'Kommentieren von Anträgen?',
    'comments_desc'  => 'Benutzer*innen können (Änderungs-)Anträge absatzweise kommentieren',
    'comments_no'    => 'Nein',
    'comments_yes'   => 'Ja',

    'agenda_title' => 'Gibt es eine Tagesordnung?',
    'agenda_desc'  => 'Auf der Startseite kann dann eine Tagesordnung festgelegt werden',
    'agenda_no'    => 'Nein',
    'agenda_yes'   => 'Ja',

    'opennow_title' => 'Die Seite sofort veröffentlichen?',
    'opennow_desc'  => 'Oder zuerst im Wartungsmodus belassen?',
    'opennow_no'    => 'Zuerst in den Wartungsmodus',
    'opennow_yes'   => 'Sofort starten',

    'sitedate_title'          => 'Fast geschafft!',
    'sitedate_desc'           => 'Noch ein paar organisatorische Angaben...',
    'sitedata_sitetitle'      => 'Name der Veranstaltung / des Programms',
    'sitedata_organization'   => 'Name der veranstaltenden Organisation',
    'sitedata_subdomain'      => 'Internet-Adresse der Seite',
    'sitedata_subdomain_hint' => 'Es sind nur Buchstaben, Zahlen, "_" und "-" möglich.',
    'sitedata_contact'        => 'Kontaktadresse',
    'sitedata_contact_hint'   => 'Name, E-Mail, postalische Adresse fürs Impressum',
    'sitedata_subdomain_err'  => 'Die Subdomain "%SUBDOMAIN%" ist nicht verfügbar.',

    'created_title'          => 'Veranstaltung angelegt',
    'created_msg'            => 'Die Veranstaltung wurde angelegt.',
    'created_goto_con'       => 'Zur neu angelegten Veranstaltung',
    'created_goto_motion'    => 'Hier kannst du nun den Antrag anlegen',
    'created_goto_manifesto' => 'Hier kannst du nun den Text eingeben',

    'sandbox_dummy_contact' => 'Test-Kontakt',
    'sandbox_dummy_orga'    => 'Organisation X',
    'sandbox_dummy_title'   => 'Test-Veranstaltung',
    'sandbox_dummy_welcome' => '<h2>Willkommen auf Antragsgrün!</h2><br><br>
                                Auf dieser Seite kannst du die Funktionen von Antragsgrün frei ausprobieren.
                                Wir haben einen Test-Admin-Benutzer angelegt, mit dem die verschiedenen administrativen Vorgänge getestet werden können:<br><br>
                                <strong>Login:</strong> %ADMIN_USERNAME%<br>
                                <strong>Passwort:</strong> %ADMIN_PASSWORD%<br><br>
                                Bitte beachte aber, dass dies nur eine Test-Seite ist und <strong>nach drei Tagen wieder gelöscht</strong> wird.<br><br>
                                <em>Diesen Text kannst du übrigens bearbeiten, indem du rechts oben auf "Bearbeiten" klickst.</em>',
];
