2.3.0 - 2014-??-??
==================

* OpenOffice-Export mit Unterstützung von Templates
* Einfügungen im kompakten PDF werden nun fett (und weiterhin unterstrichen und grün) dargestellt, um sie im Schwarz-Weiß-Druck leichter als solche erkennbar zu machen.
* Refactoring: der Code wurde etwas aufgeräumt (alte/ungenutzte Controller/Views entfernt, das ungenutzte admin-Feld in der Datenbank und die zugehörigen Abfragen) entfernt.
* Neue Antragsstati: Übernahme, Erledigt, Überweisung, Abstimmung (BDK)
* Es gibt eine spezielle Einstellung, mit der man den Antragstext auch für Admins als nachträglich unveränderlich setzen kann (BDK).

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
