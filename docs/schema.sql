SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `parteitool` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `parteitool` ;

-- -----------------------------------------------------
-- Table `parteitool`.`veranstaltung`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`veranstaltung` (
  `id` SMALLINT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(200) NOT NULL ,
  `name_kurz` VARCHAR(45) NOT NULL ,
  `antrag_einleitung` TEXT NOT NULL ,
  `datum_von` DATE NOT NULL ,
  `datum_bis` DATE NOT NULL ,
  `antragsschluss` TIMESTAMP NULL DEFAULT NULL ,
  `policy_antraege` VARCHAR(20) NULL ,
  `policy_aenderungsantraege` VARCHAR(20) NULL ,
  `policy_kommentare` VARCHAR(20) NULL ,
  `typ` TINYINT NULL ,
  `admin_email` VARCHAR(150) NULL ,
  `freischaltung_antraege` TINYINT NULL DEFAULT 1 ,
  `freischaltung_aenderungsantraege` TINYINT NULL DEFAULT 1 ,
  `freischaltung_kommentare` TINYINT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`antrag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`antrag` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `veranstaltung` SMALLINT NOT NULL ,
  `abgeleitet_von` INT NULL ,
  `typ` TINYINT NULL ,
  `name` TINYTEXT NOT NULL ,
  `revision_name` VARCHAR(50) NOT NULL ,
  `datum_einreichung` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  `datum_beschluss` VARCHAR(45) NULL ,
  `text` MEDIUMTEXT NULL ,
  `begruendung` MEDIUMTEXT NULL ,
  `status` TINYINT NULL ,
  `status_string` VARCHAR(55) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `veranstaltung` (`veranstaltung` ASC) ,
  INDEX `abgeleitet_von` (`abgeleitet_von` ASC) ,
  CONSTRAINT `fk_antrag_veranstaltung`
    FOREIGN KEY (`veranstaltung` )
    REFERENCES `parteitool`.`veranstaltung` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_antrag_antrag1`
    FOREIGN KEY (`abgeleitet_von` )
    REFERENCES `parteitool`.`antrag` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`person`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`person` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `typ` ENUM('person', 'organisation') NOT NULL ,
  `name` VARCHAR(100) NOT NULL ,
  `email` VARCHAR(200) NULL ,
  `telefon` VARCHAR(100) NULL ,
  `auth` VARCHAR(200) NULL ,
  `angelegt_datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  `admin` TINYINT NOT NULL ,
  `status` TINYINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `auth_UNIQUE` (`auth` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`antrag_unterstuetzer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`antrag_unterstuetzer` (
  `antrag_id` INT NOT NULL ,
  `unterstuetzer_id` INT NOT NULL ,
  `rolle` ENUM('initiator', 'unterstuetzt', 'mag', 'magnicht') NOT NULL ,
  `kommentar` TEXT NULL ,
  PRIMARY KEY (`antrag_id`, `unterstuetzer_id`, `rolle`) ,
  INDEX `fk_unterstuetzer` (`unterstuetzer_id` ASC) ,
  INDEX `fk_antrag` (`antrag_id` ASC) ,
  CONSTRAINT `fk_antrag`
    FOREIGN KEY (`antrag_id` )
    REFERENCES `parteitool`.`antrag` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_unterstuetzer`
    FOREIGN KEY (`unterstuetzer_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`aenderungsantrag`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`aenderungsantrag` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `antrag_id` INT NULL ,
  `revision_name` VARCHAR(45) NULL ,
  `name_neu` TINYTEXT NULL ,
  `text_neu` MEDIUMTEXT NOT NULL ,
  `begruendung_neu` MEDIUMTEXT NOT NULL ,
  `aenderung_text` MEDIUMTEXT NOT NULL ,
  `aenderung_begruendung` MEDIUMTEXT NOT NULL ,
  `datum_einreichung` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  `datum_beschluss` TIMESTAMP NULL ,
  `status` TINYINT NOT NULL ,
  `status_string` VARCHAR(55) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_aenderungsantrag_antrag1` (`antrag_id` ASC) ,
  CONSTRAINT `fk_aenderungsantrag_antrag1`
    FOREIGN KEY (`antrag_id` )
    REFERENCES `parteitool`.`antrag` (`id` )
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`aenderungsantrag_unterstuetzer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`aenderungsantrag_unterstuetzer` (
  `aenderungsantrag_id` INT NOT NULL ,
  `unterstuetzer_id` INT NOT NULL ,
  `rolle` ENUM('initiator', 'unterstuetzer', 'mag', 'magnicht') NOT NULL ,
  `kommentar` TEXT NULL ,
  PRIMARY KEY (`aenderungsantrag_id`, `unterstuetzer_id`, `rolle`) ,
  INDEX `fk_person_has_aenderungsantrag_aenderungsantrag1` (`aenderungsantrag_id` ASC) ,
  INDEX `fk_person_has_aenderungsantrag_unterstuetzers1` (`unterstuetzer_id` ASC) ,
  CONSTRAINT `fk_person_has_aenderungsantrag_unterstuetzers1`
    FOREIGN KEY (`unterstuetzer_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_person_has_aenderungsantrag_aenderungsantrag1`
    FOREIGN KEY (`aenderungsantrag_id` )
    REFERENCES `parteitool`.`aenderungsantrag` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`antrag_kommentar`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`antrag_kommentar` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `verfasser_id` INT NULL ,
  `antrag_id` INT NULL ,
  `absatz` SMALLINT NULL ,
  `text` TEXT NOT NULL ,
  `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  `status` TINYINT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_antrag_kommentar_person1` (`verfasser_id` ASC) ,
  INDEX `fk_antrag_kommentar_antrag1` (`antrag_id` ASC) ,
  CONSTRAINT `fk_antrag_kommentar_person1`
    FOREIGN KEY (`verfasser_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_antrag_kommentar_antrag1`
    FOREIGN KEY (`antrag_id` )
    REFERENCES `parteitool`.`antrag` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`aenderungsantrag_kommentar`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`aenderungsantrag_kommentar` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `verfasser_id` INT NULL ,
  `aenderungsantrag_id` INT NULL ,
  `absatz` SMALLINT NULL ,
  `text` VARCHAR(45) NULL ,
  `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ,
  `status` TINYINT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_aenderungsantrag_kommentar_person1` (`verfasser_id` ASC) ,
  INDEX `fk_aenderungsantrag_kommentar_aenderungsantrag1` (`aenderungsantrag_id` ASC) ,
  CONSTRAINT `fk_aenderungsantrag_kommentar_person1`
    FOREIGN KEY (`verfasser_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_aenderungsantrag_kommentar_aenderungsantrag1`
    FOREIGN KEY (`aenderungsantrag_id` )
    REFERENCES `parteitool`.`aenderungsantrag` (`id` )
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`antrag_abo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`antrag_abo` (
  `antrag_id` INT NOT NULL ,
  `abonnent_id` INT NOT NULL ,
  PRIMARY KEY (`antrag_id`, `abonnent_id`) ,
  INDEX `fk_antrag_has_person1_abonnent` (`abonnent_id` ASC) ,
  INDEX `fk_antrag_has_person1_antrag` (`antrag_id` ASC) ,
  CONSTRAINT `fk_antrag_has_person1_antrag`
    FOREIGN KEY (`antrag_id` )
    REFERENCES `parteitool`.`antrag` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_antrag_has_person1_abonnent`
    FOREIGN KEY (`abonnent_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`veranstaltung_abo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`veranstaltung_abo` (
  `veranstaltung_id` SMALLINT NOT NULL ,
  `abonnent_id` INT NOT NULL ,
  PRIMARY KEY (`veranstaltung_id`, `abonnent_id`) ,
  INDEX `fk_veranstaltung_has_person_person1` (`abonnent_id` ASC) ,
  INDEX `fk_veranstaltung_has_person_veranstaltung1` (`veranstaltung_id` ASC) ,
  CONSTRAINT `fk_veranstaltung_has_person_veranstaltung1`
    FOREIGN KEY (`veranstaltung_id` )
    REFERENCES `parteitool`.`veranstaltung` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_veranstaltung_has_person_person1`
    FOREIGN KEY (`abonnent_id` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `parteitool`.`texte`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`texte` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `text_id` VARCHAR(20) NOT NULL ,
  `veranstaltung_id` SMALLINT NULL ,
  `text` MEDIUMTEXT NULL ,
  `edit_datum` TIMESTAMP NULL DEFAULT NOW() ,
  `edit_person` INT NOT NULL ,
  INDEX `fk_texte_veranstaltung1` (`veranstaltung_id` ASC) ,
  INDEX `fk_texte_person1` (`edit_person` ASC) ,
  UNIQUE INDEX `veranstaltung_id_UNIQUE` (`text_id` ASC, `veranstaltung_id` ASC) ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_texte_veranstaltung1`
    FOREIGN KEY (`veranstaltung_id` )
    REFERENCES `parteitool`.`veranstaltung` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_texte_person1`
    FOREIGN KEY (`edit_person` )
    REFERENCES `parteitool`.`person` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `parteitool`.`cache`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parteitool`.`cache` (
  `id` CHAR(32) NOT NULL ,
  `datum` TIMESTAMP NULL ,
  `daten` LONGBLOB NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
