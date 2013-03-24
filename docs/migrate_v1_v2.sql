SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

INSERT INTO veranstaltungsreihe SELECT id, yii_url, name, name_kurz, 1, "", id FROM antragsgruen_alt.veranstaltung;
INSERT INTO veranstaltung SELECT id, id, name, name_kurz, antrag_einleitung, datum_von, datum_bis, antragsschluss, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, yii_url, admin_email, freischaltung_antraege, freischaltung_aenderungsantraege, freischaltung_kommentare, logo_url, fb_logo_url, ae_nummerierung_global, zeilen_nummerierung_global, bestaetigungs_emails, revision_name_verstecken, kommentare_unterstuetzbar, ansicht_minimalistisch, einstellungen FROM antragsgruen_alt.veranstaltung;
INSERT INTO texte SELECT id, text_id, veranstaltung_id, text, edit_datum FROM antragsgruen_alt.texte;
INSERT INTO person SELECT * FROM antragsgruen_alt.person;
INSERT INTO veranstaltungsreihen_admins SELECT veranstaltung_id, person_id FROM antragsgruen_alt.veranstaltung_person WHERE rolle = "admin";
INSERT INTO antrag SELECT * FROM antragsgruen_alt.antrag;
INSERT INTO aenderungsantrag SELECT * FROM antragsgruen_alt.aenderungsantrag;
INSERT INTO antrag_unterstuetzerInnen SELECT * FROM antragsgruen_alt.antrag_unterstuetzer;
INSERT INTO aenderungsantrag_unterstuetzerInnen SELECT * FROM antragsgruen_alt.aenderungsantrag_unterstuetzer;
INSERT INTO antrag_kommentar SELECT * FROM antragsgruen_alt.antrag_kommentar;
INSERT INTO aenderungsantrag_kommentar SELECT * FROM antragsgruen_alt.aenderungsantrag_kommentar;
INSERT INTO antrag_kommentar_unterstuetzerInnen SELECT * FROM antragsgruen_alt.antrag_kommentar_unterstuetzer;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

