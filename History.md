2.6.7 - 2015-??-??
==================

* Im Backend gibt es nun eine komplette Liste aller Anträge und Änderungsanträge.

2.6.6 - 2015-04-08
==================

* Bugfix: gelöschte Anträge wurden im Backend noch angezeigt.
* Wenn ein Antrag von der AntragstellerIn noch unbestätigt ist, zeigt der Link auf der Startseite unter "Meine Anträge" zum Bestätigen-Formular.
* In der Antragsliste im Admin-Backend gibt es einen zusätzlichen Filter für AntragstellerInnen.
* Sicherstellen, dass auch im Admin-Backend keine Änderungsantragsnummer doppelt vergeben werden kann (wie bei v2.6.4 bei den Anträgen) 

2.6.5 - 2015-03-24
==================

* Es gibt einen neuen Antragsstatus "Unveränderlich"

2.6.4 - 2015-03-22
==================

* Man kann im Admin-Interface nun keine Antragsnummern mehr geben, die bereits vergeben sind.
* Ein paar Anpassungen speziell für den Länderrat.
* Man kann über die ExpertInneneinstellungen nun auch Durchgestrichen als Formatierungsoption bei Anträgen zulassen.
* In der TODO-Liste der Admin-Übersicht wird bei (Änderungs-)Anträgen und Kommentaren nun auch der Verfasser angezeigt.

2.6.3 - 2015-03-07
==================

* Der OpenOffice-Export funktionierte nicht, wenn im zu exportierenden HTML-Text auch nur kleinste HTML-Fehler enthalten waren. Das wird durch einen vorgeschalteten Filter nun behoben, der fehlerhafte Tags bereinigt.

2.6.2 - 2015-03-07
==================

* Es lässt sich nun auch einstellen, dass auch Begründungen kommentierbar sind.

2.6.1 - 2015-03-06
==================

* Admins können nun Anträge nachträglich wieder auf ungeprüft zurücksetzen.
* Einige Fehler rund ums Stellen von Änderungsanträgen wurden behoben, die sich mit 2.6.0 eingeschlichen hatten.
* Es gibt einen neuen Antragstyp "Tagesordnung". In der Sortierung tauchen diese Anträge grundsätzlich ganz vorne auf.

2.6.0 - 2015-03-03
==================

* Änderungsanträge können neben Excel nun auch als OpenOffice-Spreadsheet exportiert werden (mitsamt den Formatierungen).
* Schlagworte in den ExpertInneneinstellungen lassen sich nun umsortieren.
* Auf Wunsch kann man auch die Anzahl der Schlagworte pro Antrag auf 1 begrenzen.
* Anträge lassen sich von der AntragstellerIn nun grundsätzlich bearbeiten, solange sie noch nicht freigeschaltet sind.
* Nach dem Ändern eines Antrags landete man manchmal auf einer fehlerhaften Seite.
* Der Link "Änderungsanträge einpflegen" wird nur noch angezeigt, wenn es auch Änderungsanträge gibt.
* Es kann nun eine maximale Länge des Antragstitels festgelegt werden.

2.5.4 - 2015-02-17
==================

* Schlagworte eines Antrags werden nun beim Excel-Export in einer eigenen Spalte angezeigt.
* Der Titel eines Antrags wird beim Excel-Export nun in einer eigenen Spalte angezeigt.


2.5.3 - 2015-02-07
==================

* Bugfix: Das AntragsstellerInnen-Formular wurde beim Stellen eines Antrags oft nicht angezeigt.

2.5.2 - 2015-01-27
==================

* Das Löschen von Anträgen im Admin-Backend funktionierte nicht
* Update auf CKEditor 4.4.7


2.5.1 - 2015-01-10
==================

* Die Funktion "Thema hinzufügen" bei der normalen Antragsansicht erscheint Admins nur noch, wenn auch Themen vorhanden sind.
* Bugfix: Das Anlegen von BenutzerInnenaccounts nach einem Wurzelwerk-Login führte zu einer Fehlermeldung (der Account wurde aber trotzdem angelegt).
* Neue Hooks für veranstaltungsspezifische Einstellungen:
  * veranstaltungsspezifisch_antrag_typ_str: Die Typen von Anträge auf der Startseite umbenennen
  * veranstaltungsspezifisch_antrag_max_len: Verschiedene maximale Textlängen in einer Veranstaltung abhängig vom Antragstyp festlegen
  * veranstaltungsspezifisch_antrag_pdf_header: Aspekte des Antrags-PDF einstellen
  * veranstaltungsspezifisch_email_from_name: Den Absendername bei E-Mails einstellen



2.5.0 - 2015-01-04
==================

* Die Veranstaltungseinstellungen wurden in zwei Seiten aufgeteilt, eine "Standard"-Seite und eine "ExpertInnen-Einstellungsseite"
* Bei Anträgen kann man eine maximale Länge in Zeichen einstellen (KV Wiesbaden)
* Als drittes "Wording" lässt sich nun "Themenvorschlag einwerfen" auswählen (KV Wiesbaden)
* Einiger unbenutzer Code wurde entfernt.
* Es gibt die Möglichkeit, Schlagworte zu definieren, denen AntragstellerInnen ihre Anträge zuordnen. (KV Wiesbaden)
* Vorbereitung: Refactoring der AntragstellerInnen-Tabellen
* Das "Feeds"-Kästchen in der Sidebar lässt sich optional ausblenden. (KV Wiesbaden)
* Auf Wunsch lässt sich das "Begründungs"-Eingabefeld bei neuen Aträgen deaktivieren. (KV Wiesbaden)
* Anträge können allgemein kommentiert werden, ohne expliziten Zeilenbezug. (ExpertInnenfunktion; KV München)
* Ein Bugfix beim Login per E-Mail-Adresse
* Bei Accounts, die nur für eine Veranstaltung gültig sind, kann man als Admin nun auch den Namen in der Admin-Maske eingeben, nicht nur die E-Mail-Adresse (KV Wiesbaden)
* Es werden an diversen Stellen nun nach und nach Hooks eingeführt, um den Code für spezielle Veranstaltungen anzupassen, ohne die Änderungen ins Repository committen zu müssen. Die Standards werden in protected/config/veranstaltungsspezifisch.std.php festgelegt, man kann sie mit protected/config/veranstaltungsspezifisch.local.php überschreiben.  
* Der Absender der E-Mail-Benachrichtigungen wurde bei einigen Mailprogrammen nicht richtig angeziegt (Probleme mit dem Umlaut)
* Bugfix: Links im Antragstext wurden nicht als solche angezeigt

2.4.13 - 2014-11-03
==================

* Änderung: Wenn Änderungsanträge nach der betroffenen Zeilennummer sortiert werden, wirkt sich diese Sortierung nun auch auf das Sammel-PDF aus. (LV Bremen)
* Feature: Beim Excel-Export der Änderungsanträge gibt es nun auch die Möglichkeit, Antragsnummer, ÄA-Nummer und Bezugszeilennummer in separate Spalten zu exportieren. (LV Bremen)

2.4.12 - 2014-10-29
==================

* Performance: Eine zusätzliche Caching-Funktion, um die Antragsanzeige bei vielen Änderungsanträgen zu beschleunigen.

2.4.11 - 2014-10-29
==================

* Bugfix: Fehlerhafte Berechnung der Zeilennummern beim Änderungsantrags-PDF, wenn Textformatierungen im Fließtext vorkamen.

2.4.10 - 2014-10-24
==================

* Bugfix: Speichern von Telefonnummern, Anzeige der Kontaktdaten bei Änderungsanträgen.

2.4.9 - 2014-10-23
==================

* Feature: Beim Excel-Export der (Änderungs-)Anträge lassen sich nun auf Wunsch Antragstext u. Begründung in einer Spalte zusammenfassen.
* Bugfix: Formatierungszeichen im Excel-Export werden nun entfernt.

2.4.8 - 2014-10-15
==================

* Bugfix: Die Einleitung unter der Überschrift wurde bei Änderungsantrags-PDFs nicht richtig formatiert.

2.4.7 - 2014-10-15
==================

* Bugfix: Beim PDF-Export wurden beim Änderungsantragstext häufiger Leerzeichen "verschluckt".
* Bugfix: Die Gzip-Kompressions-Routine verzögerte die Ausgabe bei den Kommandozeilenaufrufen.

2.4.6 - 2014-10-13
==================

* Bugfix: Fehlerhafte Steuerzeichen ([COLOR] u. =10.0pt) werden jetzt ausgefiltert.
* Bugfix: Eine Fehlerhafte Behandlung der BDK-Zugriffsregelung wurde behoben.
* Bugfix: Die Volltext-UnterstützerInnen-Eingabe beim Stellen von Änderungsanträgen funktionierte nicht.
* Bugfix: (Inzwischen) Unnötiges Escaping beim Excel-Export von (Änderungs-)Anträgen wurde entfernt.

2.4.5 - 2014-10-09
==================

* Bugfix: Das Registrieren über die Benachrichtigen-Seite funktionierte nicht.

2.4.4 - 2014-10-05
==================

* Gzip-Kompression aktivieren

2.4.3 - 2014-10-04
==================

* Die Änderungsanträge auf der Startseite unter einem Antrag werden nun sortiert angezeigt. (LV Berlin)
* Bugfixes u. Refactoring beim OpenOffice-Export (Formatierung bei Listen und Umlaute)

2.4.2 - 2014-09-28
==================

* Auf der Liste der (Änderungs-)Anträge im Admin-Backend werden jetzt auch Organisation u. Beschlussdatum bei der AntragstellerIn angezeigt.
* Beim Excel-Export der (Änderungs-)Anträge für Admins weren nun auch die Kontaktdaten der AntragstellerInnen mitgeliefert. (BDK)
* Bugfix: Beim Einreichen von Anträgen im Namen anderer (Admin-Funktion) wurde die Telefonnummer / E-Mail-Adresse manchmal vom Admin übernommen statt von den eingegebenen Daten. (BDK)

2.4.1 - 2014-09-22
==================

* Die AntragunterstützerInnen werden jetzt auch im PDF des (Änderungs-)Antrags angezeigt. (LV Bayern)

2.4.0 - 2014-09-18
==================

* OpenOffice-Export mit Unterstützung von Templates (noch Alpha, BDK)
* Bei (Änderungs-)Antragsbegründungen lassen sich nun einige erweiterte Formatierungen aktivieren, wie beispielsweise Tabellen oder Zitate. (noch Alpha, BDK)
* Bugfix: Beim Copy/Paste von Texten in das Textformular gingen gelegentlich Zeilenumbrüche verloren.
* "Antragstitel" heißt jetzt "Überschrift" (BDK)
* Weitere Stati: Pausiert, Informationen fehlen, Nicht zugelassen
* Der Status-Erweiterungstext aus dem Backend wird immer mit angezeigt, sofern vorhanden.
* Bei Anträgen und Änderungsanträgen gibt es nun im Admin-Backend ein internes Feld "Interne Notiz", die nur Admins sehen können.
* Das Beschlussdatum bei Gremien, die einen (Änderungs-)Antrag einreichen, wurde an vielen Stellen noch nicht angezeigt.
* Eine Einstellungemöglichkeit, um das Login nur auf das Wurzelwerk zu beschränken (BDK)
* Im Backend lässt sich die Liste der (Änderungs-)Anträge nun nach Status filtern (BDK)
* Wenn eingestellt ist, dass Anträge nachträglich unveränderlich sind, gilt das nun erst ab dem Zeitpunkt, wenn diese auch freigeschaltet werden.
* Excel-Export der Anträge (BDK)

2.3.0 - 2014-09-08
==================

* Redaktionelle Änderungsanträge
* Einfügungen im kompakten PDF werden nun fett (und weiterhin unterstrichen und grün) dargestellt, um sie im Schwarz-Weiß-Druck leichter als solche erkennbar zu machen. (BDK)
* Refactoring: der Code wurde etwas aufgeräumt (viele alte/ungenutzte Controller/Views entfernt, das ungenutzte admin-Feld in der Datenbank und die zugehörigen Abfragen) entfernt.
* Neue Antragsstati: Übernahme, Erledigt, Überweisung, Abstimmung (BDK)
* Es gibt eine spezielle Einstellung, mit der man den Antragstext auch für Admins als nachträglich unveränderlich setzen kann (BDK).
* Die angegebene E-Mail-Adresse und Telefonnummer der AntragstellerIn wird nun auf der Antragsseite angezeigt, wenn man als Admin einer Veranstaltung eingeloggt ist.
* Bugfix: manche Änderungsanträge konnten nicht angezeigt werden und führten zu einer "Undefined offset"-Fehlermeldung
* Bugfix: Eine mögliche XSS-Attacke in Antragsbegründungen wurde geschlossen
* Bugfix: Das "korrigieren" von Anträgen vor der offiziellen Einreichung funktionierte nicht, wenn eine UnterstützerInnenliste nötig ist.
* Bugfix: Die Zeilenlängenberechnung funktionierte in einigen Randfällen noch nicht korrekt (konkret: Gedankenstriche, die als 81.Zeichen auftraten, wenn die Zeilenlänge max. 80 Zeichen beträgt)
* Bugfix: Beim automatischen Setzen von Revisionsnummern a la "A1", "A2" wurden bereits gelöschte Anträge mitgezählt, sodass Lücken in der Nummerierung entstanden.

2.2.1 - 2014-09-04
==================

* Man kann sich nun auch im Login-Formular selbst einen neuen E-Mail-basierten Zugang einrichten.

2.2.0 - 2014-09-03
==================

Bugfixes:
* Beim Fahren mit der Maus über ein Änderungsantrag-Lesezeichen am rechten Rand eines Antrags verschoben sich manchmal die Zeilen überhalb der tatsächlichen Änderung.
* Einige Kompatibilitätsprobleme mit Datenbanken (bei leeren Feldern ohne Default-Wert) beheben.
* CKEditor: Update auf Version 4.4.4
* Größere mitgelieferte Favicons, damit Lesezeichen (insb. auch auf iOS/Android/WindowsPhone) weniger verpixelt aussehen.
* Die Zeilennummerierung war bei Listen nach rechts verschoben.

Features:
* Änderungsanträge werden standardmäßig in der "Diff-Ansicht" dargestellt. Für jeden "Ersetze [x] durch [y]"-Block (der jetzt "Ändere den Absatz wie folgt: [x]" heißt) gibt es jetzt genau einen Kommentar-Button, statt vier wie bisher. (LV Hessen)
* Es gibt nun eine einheitliche Einstellungsmöglichkeit, um die Angabe der E-Mail-Adresse und nun auch der Telefonnummer beim Anlegen von Anträgen zu konfigurieren.
* Berechtigungs-Alternative: "Gremium oder Delegierte": Entweder 20 AntragstellerInnen (mit vereinfachter Copy/Paste-Eingabe von vielen UnterstützerInnen) oder ein Gremium (dann verpflichtende Angabe des Beschlussdatums). (BDK)
* Berechtigungs-Alternative: Min. 5 AntragstellerInnen oder ein Gremium (LV Hessen)
* Accounts, die nur für eine Veranstaltungsreihe gültig sind


2.1.2 - 2014-08-17
==================

* Die kompakte PDF-Version von Änderungsanträgen zeigte die Zeilen arg verunstaltet an.

2.1.1 - 2014-08-08
==================

* Veranstaltungsadmins können nun auch (übers Frontend) Anträge im Namen anderer einreichen.
* Unterstützung von 4-Byte UTF-8-Zeichen (z.B. Emoji).
* Es gibt keine Login-Warnung beim Anlegen von Anträgen mehr, wenn man nicht eingeloggt ist.
* Layout-Bugfix bei der Wartungsmodus-Nachricht.

2.1.0 - 2014-08-08
==================

* Beginn der Versionszählung / dieses Changelogs
