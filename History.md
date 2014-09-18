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
