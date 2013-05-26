SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

INSERT INTO veranstaltungsreihe SELECT id, yii_url, name, name_kurz, 1, "", id, 1, "" FROM antragsgruen.veranstaltung;
INSERT INTO veranstaltung SELECT id, id, name, name_kurz, datum_von, datum_bis, antragsschluss, policy_antraege, policy_aenderungsantraege, policy_kommentare, policy_unterstuetzen, typ, yii_url, admin_email, einstellungen FROM antragsgruen.veranstaltung;
INSERT INTO texte SELECT id, text_id, veranstaltung_id, text, edit_datum FROM antragsgruen.texte;
INSERT INTO person SELECT * FROM antragsgruen.person;
INSERT INTO veranstaltungsreihen_admins SELECT veranstaltung_id, person_id FROM antragsgruen.veranstaltung_person WHERE rolle = "admin";
INSERT INTO antrag SELECT * FROM antragsgruen.antrag;
INSERT INTO aenderungsantrag SELECT * FROM antragsgruen.aenderungsantrag;
INSERT INTO antrag_unterstuetzerInnen SELECT * FROM antragsgruen.antrag_unterstuetzer;
INSERT INTO aenderungsantrag_unterstuetzerInnen SELECT * FROM antragsgruen.aenderungsantrag_unterstuetzer;
INSERT INTO antrag_kommentar SELECT *, 0 FROM antragsgruen.antrag_kommentar;
INSERT INTO aenderungsantrag_kommentar SELECT *, 0 FROM antragsgruen.aenderungsantrag_kommentar;
INSERT INTO antrag_kommentar_unterstuetzerInnen SELECT * FROM antragsgruen.antrag_kommentar_unterstuetzer;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

