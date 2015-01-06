Antragsgrün
===========

Antragsgrün ist ein Antragsverwaltungssystem, das mit der [Netzbegrünung](http://blog.netzbegruenung.de/) entwickelt wird.

Es ist auf zwei Szenarien zugeschnitten:
- Das Kommentieren von Dokumenten - insbesondere des Wahlprogramms
- Die Vorbereitung von Parteitagen - insbesondere das Einreichen und Versionieren von Anträgen, das Kommentieren von Anträgen und das Einreichen von Änderungsanträgen.

Es ist NICHT:
- Ein Abstimmungstool. Eine eventuelle Beschlussfassung über Anträge findet auf andere Weise statt (Parteitag, Programmkommission, etc.)
- Ein "Vor-Ort"-Tool. Dafür empfehle ich [OpenSlides](http://openslides.org/de/) . Ich strebe mittelfristig an, den Export von Anträgen von Antragsgrün zu Openslides soweit möglich zu erleichtern.

Login und Berechtigungen
------------------------
Wichtig ist uns eine möglichst niederschwellige Benutzung. Ein Login ist zum Schreiben von Kommentaren, Änderungsanträgen und Anträgen nicht unbedingt nötig. Ggf. müssen (Änderungs-)Anträge aber von einem Admin freigeschaltet werden, um Spam zu verhindern.

Ein Login ist auf zweieinhalb Weisen möglich:
- Über Antragsgrün-eigene Accounts, die über E-Mail-Adresse und Passwort angelegt werden.
- Über beliebige OpenID-Provider.
- Über die Wurzelwerk-Logindaten (technisch gesehen auch OpenID, wegen der Wichtigkeit im Umfeld von Antragsgrün aber als eigener Login-Mechanismus dargestellt).

Ob ein Login nötig ist, und ob Anträge / Änderungsanträge erst freigeschaltet werden müssen, ist pro Veranstaltung / Wahlprogramm separat einstellbar.

Differenziertere Rechtestrukturen, wie z.B. dass für die Antragsstellung für einen Parteitag auch XY UnterstützerInnen angegeben werden müssen, sind mit wenig (aber nicht ganz ohne) Programmierarbeit einzurichten.

Textfluss / Absätze
-------------------
Wichtig ist uns, das von klassischen "Offline-Anträgen" her bekannte System von definierten Zeilennummern beizubehalten. Eine Zeile hat maximal 80 Zeichen, bei eingerückten Listen bzw. Zitaten maximal 60 Zeichen.

Kommentare und Änderungsanträge beziehen sich jeweils auf ganze Absätze. Ein neuer Absatz beginnt nach einer Leerzeile.

Als Textformatierungen sind Fett/Kursivschrift möglich, Unter- und Durchstreichungen, Links, (un)nummerierte Listen und eingerückte Kommentare.

Versionierung / Diffs
---------------------
Bei jedem Änderungsantrag ist muss der/die AntragsstellerIn auch angeben, wie der Antrag bzw. die zu ändernden Absätze danach aussehen soll. Ein Änderungsantrag kann auch mehrere Absätze ändern, neue einfügen bzw. löschen.

Beim betreffenden Antrag wird an den betreffenden Absätzen markiert, dass hierzu Änderungsanträge (und ggf. Kommentare) vorliegen. Die Änderungen können auch in "Diff-Ansicht" angezeigt werden, das heißt, dass neu eingefügte Textpassagen grün erscheinen, gelöschte rot durchgestrichen.

[TODO] Wenn der/die AntragsstellerIn sich entschließt, eine neue Version des Antrags zu erstellen (A1neu), ist eine Änderungsansicht zum vorigen Antrag verfügbar.

Export
------
Anträge, Änderungsanträge und Kommentare können auf folgende Weisen exportiert werden:
- Anträge gibt es als PDFs, einzeln und als Sammel-PDF für eine gesamte Veranstaltung
- Für Admins: Anträge gibt es als minimal formatiertes HTML, von wo aus es z.B. in Word importiert werden kann.
- Änderungsanträge gibt es als PDFs, in einer "normalen" Fassung (Originalfassung und Neufassung stehen untereinander) und einer "kompakten" Fassung (Bearbeitungen werden Wiki-mäßig farbig angezeigt)
- Für Admins: Alle Änderungsanträge einer Veranstaltung gibt es als gesammelte Excel-Datei.
- Für Admins: Alle Kommentare einer Veranstaltung gibt es als gesammelte Excel-Datei.

RSS-Feeds
---------
Es gibt je einen RSS-Feed über neue Anträge zu einer Veranstaltung, neue Änderungsanträge bzgl. einer Veranstaltung und neue Kommentare zu einem Antrag einer Veranstaltung. Außerdem einen Sammel-Feed, in denen alle Einträge der drei vorigen Feeds auftauchen.

[TODO] Es wird einen Feed pro Antrag geben, in dem neue Änderungsanträge und Kommentare zu dem betreffenden Antrag gelistet werden.

Produktivversion
----
Hier läuft die Produktivversion von Antragsgrün: https://www.antragsgruen.de/




Installation
------------

Benötigte PHP-Module:
- mcrypt
- curl
- zip (für OpenOffice-Export)
- Intl
- dom (für OpenOffice-Export)

Benötigte PHP-Einstellungen:
- short_open_tag = On

Webserver-Konfiguration:
- Apache: mod_rewrite muss aktiv sein
- nginx: [Beispiel-Konfigurationsdatei](docs/nginx.sample.conf)

Datenbank anlegen:
- Eine Datenbank u. Benutzer für Antragsgrün anlegen; Antragsgrün braucht mindestens Rechte für SELECT/INSERT/UPDATE/DELETE
- cat docs/schema.sql | mysql -u [benutzername] -p -h localhost [datenbankname]
- cat docs/init-data.sql | mysql -u [benutzername] -p -h localhost [datenbankname]

Konfigurationsdatei erstellen:
- cp protected/config/main.template.php protected/config/main.php
- [vi|emacs] protected/config/main.php
- Besonders wichtige Teile der Konfigurationsdatei:
-- SEED_KEY: zufälligen String setzen
-- 'name' (Anfang): Name der Installation
-- 'db' (Mitte): Die Datenbank-Konfiguration
-- 'params' (Ende): Diverse Parameter der Installation

Berechtigungen setzen (Linux):
- chown www-data:www-data protected/runtime
- chown www-data:www-data html/assets

Berechtigungen setzen (MacOS):
- chown _www:_www protected/runtime
- chown _www:_www html/assets

Abhängigkeiten installieren:
- Zuerst [Composer](https://getcomposer.org/doc/00-intro.md) installieren
- composer install

Antragsgrün sollte nun schon funktionieren.
Es ist eine sehr simpel gehaltene Veranstaltung voreingestellt und ein Admin-Nutzer angelegt:
- Benutzername / E-Mail: "admin@test.de"
- Passwort: "admin" (ohne Anführungszeichen)


Tipps für den Betrieb:
----------------------

Ändern des Passworts eines Accounts von der Kommandozeile aus:
``
cd protected/
./yiic setze_passwort "email@account.de" "neues_passwort"
``


Kontakt:
--------
- tobias@hoessl.eu
- [@TobiasHoessl](https://twitter.com/TobiasHoessl)
- https://www.hoessl.eu/
