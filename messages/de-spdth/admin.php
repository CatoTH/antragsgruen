<?php

return [
    'con_show_ad'             => '„Dein Antragstool“ in der Sidebar anzeigen',

    'siteacc_add_ww_btn'         => 'Via SDP-Login',
    'siteacc_email_text_pre'     => 'Hallo,
wir haben dir soeben einen Zugang zu unserer SPD-Landesparteitags-Seite eingerichtet.
Dort können Anträge eingereicht, angesehen und Änderungsanträge gestellt werden.
Bei Fragen und Problemen könnt ihr euch an thueringen@spd.de wenden.
Hier ist der Zugang:

%LINK%
%ACCOUNT%

Mit solidarischen Grüßen

SPD Thüringen',
    'siteacc_acc_expl_mail'      => '<h3>Erklärung:</h3>
Um Benutzer*innen anzulegen, gib hier die E-Mail-Adressen der Mitglieder ein.
Diese Mitglieder bekommen daraufhin eine Benachrichtigungs-E-Mail zugesandt.<br>
Falls sie noch keinen eigenen Zugang im Antragstool hatten, wird automatisch einer eingerichtet
und an der Stelle von <strong>%ACCOUNT%</strong> erscheinen die Zugangsdaten
(ansonsten verschwindet das %ACCOUNT% ersatzlos).<br>
<strong>%LINK%</strong> wird immer durch einen Link auf die Antragstool-Seite ersetzt.',
    'siteacc_acc_expl_nomail'    => '<h3>Erklärung:</h3>
Um Benutzer*innen anzulegen, gib hier die E-Mail-Adressen, die Namen und die Passwörter der Mitglieder ein.
Da <strong>kein E-Mail-Versand</strong> eingerichtet ist, musst du die <strong>Passwörter</strong> hier selbst erzeugen, im Klartext eingeben und selbst an die Nutzer*innen schicken.<br><br>' .
                                    'Aus <strong>Datenschutzgründen</strong> wäre empfehlenswerter, zunächst den E-Mail-Versand einzurichten, damit das Antragstool automatisch Passwörter erzeugen und direkt an die Nutzer*innen schicken kann.',
    'siteacc_new_saml_ww'        => 'SPD-Account-Namen',
    'siteacc_mail_yourdata'      => "Du kannst dich mit folgenden Angaben einloggen:\nBenutzer*innenname: %EMAIL%\n" .
                                    "Passwort: %PASSWORD%",
    'siteacc_mail_youracc'       => 'Du kannst dich mit deinem Benutzer*innenname %EMAIL% einloggen.',
    'sitacc_admmail_subj'        => 'SPD-Parteitag-Antragstool-Administration',
    'sitacc_admmail_body'        => "Hallo!\n\nDu hast eben Admin-Zugang zu folgender SPD-Antragstool-Seite bekommen: %LINK%\n\n" .
                                    "%ACCOUNT%\n\nMit solidarischen Grüßen\nSPD-Thüringen",
    'translating_hint'           => 'Auf dieser Seite können die Texte der Benutzeroberfläche vom Antragstool angepasst werden. Falls du eine komplett neue Sprache anlegen und allen anderen Nutzer*innen von Antragstool bereit stellen willst, <a href="https://github.com/CatoTH">melde dich</a> einfach bei uns.<br><br>' .
                                    '<strong>Hinweis:</strong> Änderungen hier wirken sich nur auf die aktuelle Veranstaltung aus - auf keine andere.',

    'php_version_hint_text'  => 'Auf diesem Server läuft noch PHP in der veralteten Version %CURR%. Zukünftige Versionen vom Antragstool sind damit nicht mehr kompatibel. Bitte aktualisiere bald.',

];
