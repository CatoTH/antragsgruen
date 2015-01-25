SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `site`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `site` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `subdomain` VARCHAR(45) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `title_short` VARCHAR(100) NULL,
  `settings` BLOB(45) NULL,
  `current_consultation_id` INT NULL,
  `public` TINYINT NULL DEFAULT 1,
  `contact` MEDIUMTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `subdomain_UNIQUE` (`subdomain` ASC),
  INDEX `fk_veranstaltungsreihe_veranstaltung1_idx` (`current_consultation_id` ASC),
  CONSTRAINT `fk_site_consultation`
    FOREIGN KEY (`current_consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `site_id` INT NOT NULL,
  `url_path` VARCHAR(45) NULL,
  `type` TINYINT NULL,
  `title` VARCHAR(200) NOT NULL,
  `title_short` VARCHAR(45) NOT NULL,
  `event_date_from` DATE NULL,
  `event_date_until` DATE NULL,
  `deadline_motions` TIMESTAMP NULL DEFAULT NULL,
  `deadline_amendments` TIMESTAMP NULL DEFAULT NULL,
  `policy_motions` VARCHAR(20) NULL,
  `policy_amendments` VARCHAR(20) NULL,
  `policy_comments` VARCHAR(20) NULL,
  `policy_support` VARCHAR(20) NULL,
  `admin_email` VARCHAR(150) NULL,
  `settings` BLOB NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `yii_url_UNIQUE` (`url_path` ASC, `site_id` ASC),
  INDEX `fk_consultation_site_idx` (`site_id` ASC),
  CONSTRAINT `fk_veranstaltung_veranstaltungsreihe1`
    FOREIGN KEY (`site_id`)
    REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `parent_motion_id` INT NULL,
  `title` TEXT NOT NULL,
  `title_prefix` VARCHAR(50) NOT NULL,
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `date_resolution` VARCHAR(45) NULL,
  `text` LONGTEXT NULL,
  `explanation` LONGTEXT NULL,
  `explanation_html` TINYINT NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL,
  `status_string` VARCHAR(55) NULL,
  `note_internal` TEXT NULL,
  `cache_line_number` MEDIUMINT UNSIGNED NOT NULL,
  `cache_paragraph_number` MEDIUMINT UNSIGNED NOT NULL,
  `text_fixed` TINYINT(4) NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `consultation` (`consultation_id` ASC),
  INDEX `parent_motion` (`parent_motion_id` ASC),
  CONSTRAINT `fk_site_parent`
    FOREIGN KEY (`parent_motion_id`)
    REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_motion_consultation`
    FOREIGN KEY (`consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `motion_id` INT NULL,
  `title_prefix` VARCHAR(45) NULL,
  `changed_title` TEXT NULL,
  `changed_paragraphs` LONGTEXT NOT NULL,
  `changed_explanation` LONGTEXT NOT NULL,
  `change_metatext` LONGTEXT NOT NULL,
  `change_text` LONGTEXT NOT NULL,
  `change_explanation` LONGTEXT NOT NULL,
  `change_explanation_html` TINYINT NOT NULL DEFAULT 0,
  `cache_first_line_changed` MEDIUMINT(9) NOT NULL,
  `cache_first_line_rel` TEXT NULL,
  `cache_first_line_abs` TEXT NULL,
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `date_resolution` TIMESTAMP NULL,
  `status` TINYINT NOT NULL,
  `status_string` VARCHAR(55) NOT NULL,
  `note_internal` TEXT NULL,
  `text_fixed` TINYINT(4) NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `fk_motion_idx` (`motion_id` ASC),
  CONSTRAINT `fk_ammendment_motion`
    FOREIGN KEY (`motion_id`)
    REFERENCES `amendment` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `email` VARCHAR(200) NULL,
  `email_confirmed` TINYINT NULL DEFAULT 0,
  `auth` VARCHAR(190) NULL,
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status` TINYINT NOT NULL,
  `pwd_enc` VARCHAR(100) NULL,
  `auth_key` VARCHAR(100) NULL,
  `site_namespace_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `auth_UNIQUE` (`auth` ASC),
  INDEX `fk_user_namespace_idx` (`site_namespace_id` ASC),
  CONSTRAINT `fk_user_namespace`
    FOREIGN KEY (`site_namespace_id`)
    REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendment_comment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendment_comment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `amendment_id` INT NULL,
  `paragraph` SMALLINT NULL,
  `text` MEDIUMTEXT NULL,
  `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status` TINYINT NULL,
  `reply_notification` TINYINT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_amendment_comment_user_idx` (`user_id` ASC),
  INDEX `fk_amendment_comment_amendment_idx` (`amendment_id` ASC),
  CONSTRAINT `fk_amendment_comment_amendment`
    FOREIGN KEY (`amendment_id`)
    REFERENCES `amendment` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_amendment_comment_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendment_supporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendment_supporter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `amendment_id` INT NOT NULL,
  `position` SMALLINT NOT NULL DEFAULT 0,
  `user_id` INT NOT NULL,
  `role` ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment` MEDIUMTEXT NULL,
  `person_type` TINYINT NULL,
  `name` TEXT NULL,
  `organization` TEXT NULL,
  `resolution_date` DATE NULL DEFAULT NULL,
  `contact_email` VARCHAR(100) NULL,
  `contact_phone` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_amendment_idx` (`amendment_id` ASC),
  INDEX `fk_supporter_idx` (`user_id` ASC),
  CONSTRAINT `fk_support_amendment`
    FOREIGN KEY (`amendment_id`)
    REFERENCES `amendment` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_support_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion_subscription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion_subscription` (
  `motion_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  PRIMARY KEY (`motion_id`, `user_id`),
  INDEX `fk_motion_id` (`motion_id` ASC),
  INDEX `fk_user_id` (`user_id` ASC),
  CONSTRAINT `fk_subscription_motion`
    FOREIGN KEY (`motion_id`)
    REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subscription_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion_comment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion_comment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `motion_id` INT NULL,
  `paragraph` SMALLINT NULL,
  `text` MEDIUMTEXT NOT NULL,
  `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status` TINYINT NULL,
  `reply_notification` TINYINT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `fk_comment_user_idx` (`user_id` ASC),
  INDEX `fk_comment_notion_idx` (`motion_id` ASC),
  CONSTRAINT `fk_motion_comment_motion`
    FOREIGN KEY (`motion_id`)
    REFERENCES `motion` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_motion_comment_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion_comment_supporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion_comment_supporter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip_hash` CHAR(32) NULL,
  `cookie_id` INT NULL,
  `motion_comment_id` INT NOT NULL,
  `likes` TINYINT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `ip_hash_motion` (`ip_hash` ASC, `motion_comment_id` ASC),
  UNIQUE INDEX `cookie_motion` (`cookie_id` ASC, `motion_comment_id` ASC),
  INDEX `fk_motion_comment_supporter_comment_idx` (`motion_comment_id` ASC),
  CONSTRAINT `fk_motion_comment_supporter_comment`
    FOREIGN KEY (`motion_comment_id`)
    REFERENCES `motion_comment` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion_supporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion_supporter` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `motion_id` INT NOT NULL,
  `position` SMALLINT NOT NULL DEFAULT 0,
  `user_id` INT NOT NULL,
  `role` ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment` MEDIUMTEXT NULL,
  `person_type` TINYINT NULL,
  `name` TEXT NULL,
  `organization` TEXT NULL,
  `resolution_date` DATE NULL DEFAULT NULL,
  `contact_email` VARCHAR(100) NULL,
  `contact_phone` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_supporter_idx` (`user_id` ASC),
  INDEX `fk_motion_idx` (`motion_id` ASC),
  CONSTRAINT `fk_motion`
    FOREIGN KEY (`motion_id`)
    REFERENCES `motion` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_supporter`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `cache`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache` (
  `id` CHAR(32) NOT NULL,
  `datum` TIMESTAMP NULL,
  `data` LONGBLOB NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation_text`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_text` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `consultation_id` INT NULL,
  `text_id` VARCHAR(20) NOT NULL,
  `text` LONGTEXT NULL,
  `edit_date` TIMESTAMP NULL DEFAULT NOW(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `consultation_text_unique` (`text_id` ASC, `consultation_id` ASC),
  INDEX `fk_texts_consultation_idx` (`consultation_id` ASC),
  CONSTRAINT `fk_texts_consultation`
    FOREIGN KEY (`consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation_admin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_admin` (
  `consultation_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  PRIMARY KEY (`consultation_id`, `user_id`),
  INDEX `fk_consultation_user_idx` (`user_id` ASC),
  INDEX `fk_consultation_idx` (`consultation_id` ASC),
  CONSTRAINT `fk_consultation_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_consultation`
    FOREIGN KEY (`consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation_subscription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_subscription` (
  `consultation_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `motions` TINYINT NULL,
  `amendments` TINYINT NULL,
  `comments` TINYINT NULL,
  PRIMARY KEY (`consultation_id`, `user_id`),
  INDEX `fk_consultation_idx` (`consultation_id` ASC),
  INDEX `fk_user_idx` (`user_id` ASC),
  CONSTRAINT `fk_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_consultation`
    FOREIGN KEY (`consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `site_admin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_admin` (
  `site_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  PRIMARY KEY (`site_id`, `user_id`),
  INDEX `fk_user_idx` (`user_id` ASC),
  INDEX `fk_site_idx` (`site_id` ASC),
  CONSTRAINT `fk_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_site`
    FOREIGN KEY (`site_id`)
    REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `email_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `to_email` VARCHAR(200) NULL,
  `to_user_id` INT NULL DEFAULT NULL,
  `type` SMALLINT NULL,
  `from_email` VARCHAR(200) NULL,
  `date_sent` TIMESTAMP NULL,
  `subject` VARCHAR(200) NULL,
  `text` MEDIUMTEXT NULL,
  INDEX `fk_mail_log_user_idx` (`to_user_id` ASC),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mail_log_user`
    FOREIGN KEY (`to_user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation_odt_template`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_odt_template` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `consultation_id` INT NOT NULL,
  `type` TINYINT NOT NULL,
  `data` BLOB NOT NULL,
  INDEX `fk_consultation_idx` (`consultation_id` ASC),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_odt_templates`
    FOREIGN KEY (`consultation_id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation_tag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_tag` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `consultation_id` INT NULL DEFAULT NULL,
  `position` SMALLINT NULL,
  `title` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_consultation`
    FOREIGN KEY (`id`)
    REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `motion_tag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion_tag` (
  `motion_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`motion_id`, `tag_id`),
  INDEX `fk_tag_idg` (`tag_id` ASC),
  CONSTRAINT `fk_motion`
    FOREIGN KEY (`motion_id`)
    REFERENCES `motion_comment` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tag`
    FOREIGN KEY (`tag_id`)
    REFERENCES `consultation_tag` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
