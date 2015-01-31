SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Table `site`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `site` (
  `id`                    INT          NOT NULL AUTO_INCREMENT,
  `subdomain`             VARCHAR(45)  NOT NULL,
  `title`                 VARCHAR(200) NOT NULL,
  `titleShort`            VARCHAR(100) NULL,
  `settings`              BLOB(45)     NULL,
  `currentConsultationId` INT          NULL,
  `public`                TINYINT      NULL     DEFAULT 1,
  `contact`               MEDIUMTEXT   NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `subdomain_UNIQUE` (`subdomain` ASC),
  INDEX `fk_veranstaltungsreihe_veranstaltung1_idx` (`currentConsultationId` ASC),
  CONSTRAINT `fk_site_consultation`
  FOREIGN KEY (`currentConsultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation` (
  `id`                 INT          NOT NULL AUTO_INCREMENT,
  `siteId`             INT          NOT NULL,
  `urlPath`            VARCHAR(45)  NULL,
  `type`               TINYINT      NULL,
  `title`              VARCHAR(200) NOT NULL,
  `titleShort`         VARCHAR(45)  NOT NULL,
  `eventDateFrom`      DATE         NULL,
  `eventDateUntil`     DATE         NULL,
  `deadlineMotions`    TIMESTAMP    NULL     DEFAULT NULL,
  `deadlineAmendments` TIMESTAMP    NULL     DEFAULT NULL,
  `policyMotions`      VARCHAR(20)  NULL,
  `policyAmendments`   VARCHAR(20)  NULL,
  `policyComments`     VARCHAR(20)  NULL,
  `policySupport`      VARCHAR(20)  NULL,
  `adminEmail`         VARCHAR(150) NULL,
  `settings`           BLOB         NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `yii_url_UNIQUE` (`urlPath` ASC, `siteId` ASC),
  INDEX `fk_consultation_siteIdx` (`siteId` ASC),
  CONSTRAINT `fk_veranstaltung_veranstaltungsreihe1`
  FOREIGN KEY (`siteId`)
  REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motion` (
  `id`                   INT                NOT NULL AUTO_INCREMENT,
  `consultationId`       INT                NOT NULL,
  `parentMotionId`       INT                NULL,
  `title`                TEXT               NOT NULL,
  `titlePrefix`          VARCHAR(50)        NOT NULL,
  `dateCreation`         TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `dateResolution`       VARCHAR(45)        NULL,
  `text`                 LONGTEXT           NULL,
  `explanation`          LONGTEXT           NULL,
  `explanationHtml`      TINYINT            NOT NULL DEFAULT 0,
  `status`               TINYINT            NOT NULL,
  `statusString`         VARCHAR(55)        NULL,
  `noteInternal`         TEXT               NULL,
  `cacheLineNumber`      MEDIUMINT UNSIGNED NOT NULL,
  `cacheParagraphNumber` MEDIUMINT UNSIGNED NOT NULL,
  `textFixed`            TINYINT(4)         NULL     DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `consultation` (`consultationId` ASC),
  INDEX `parent_motion` (`parentMotionId` ASC),
  CONSTRAINT `fk_site_parent`
  FOREIGN KEY (`parentMotionId`)
  REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_motion_consultation`
  FOREIGN KEY (`consultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendment` (
  `id`                    INT          NOT NULL AUTO_INCREMENT,
  `motionId`              INT          NULL,
  `titlePrefix`           VARCHAR(45)  NULL,
  `changedTitle`          TEXT         NULL,
  `changedParagraphs`     LONGTEXT     NOT NULL,
  `changedExplanation`    LONGTEXT     NOT NULL,
  `changeMetatext`        LONGTEXT     NOT NULL,
  `changeText`            LONGTEXT     NOT NULL,
  `changeExplanation`     LONGTEXT     NOT NULL,
  `changeExplanationHtml` TINYINT      NOT NULL DEFAULT 0,
  `cacheFirstLineChanged` MEDIUMINT(9) NOT NULL,
  `cacheFirstLineRel`     TEXT         NULL,
  `cacheFirstLineAbs`     TEXT         NULL,
  `dateCreation`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `dateResolution`        TIMESTAMP    NULL,
  `status`                TINYINT      NOT NULL,
  `statusString`          VARCHAR(55)  NOT NULL,
  `noteInternal`          TEXT         NULL,
  `textFixed`             TINYINT(4)   NULL     DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `fk_motionIdx` (`motionId` ASC),
  CONSTRAINT `fk_ammendment_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `amendment` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `name`            TEXT         NOT NULL,
  `email`           VARCHAR(200) NULL,
  `emailConfirmed`  TINYINT      NULL     DEFAULT 0,
  `auth`            VARCHAR(190) NULL,
  `dateCreation`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status`          TINYINT      NOT NULL,
  `pwdEnc`          VARCHAR(100) NULL,
  `authKey`         BINARY(100)  NULL,
  `siteNamespaceId` INT          NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `auth_UNIQUE` (`auth` ASC),
  INDEX `fk_user_namespaceIdx` (`siteNamespaceId` ASC),
  CONSTRAINT `fk_user_namespace`
  FOREIGN KEY (`siteNamespaceId`)
  REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendmentComment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendmentComment` (
  `id`                INT        NOT NULL AUTO_INCREMENT,
  `userId`            INT        NULL,
  `amendmentId`       INT        NULL,
  `paragraph`         SMALLINT   NULL,
  `text`              MEDIUMTEXT NULL,
  `dateCreated`       TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status`            TINYINT    NULL,
  `replyNotification` TINYINT    NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_amendment_comment_userIdx` (`userId` ASC),
  INDEX `fk_amendment_comment_amendmentIdx` (`amendmentId` ASC),
  CONSTRAINT `fk_amendment_comment_amendment`
  FOREIGN KEY (`amendmentId`)
  REFERENCES `amendment` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_amendment_comment_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `amendmentSupporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `amendmentSupporter` (
  `id`             INT                                                NOT NULL AUTO_INCREMENT,
  `amendmentId`    INT                                                NOT NULL,
  `position`       SMALLINT                                           NOT NULL DEFAULT 0,
  `userId`         INT                                                NOT NULL,
  `role`           ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT                                         NULL,
  `personType`     TINYINT                                            NULL,
  `name`           TEXT                                               NULL,
  `organization`   TEXT                                               NULL,
  `resolutionDate` DATE                                               NULL     DEFAULT NULL,
  `contactEmail`   VARCHAR(100)                                       NULL,
  `contactPhone`   VARCHAR(100)                                       NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_amendmentIdx` (`amendmentId` ASC),
  INDEX `fk_supporter_idx` (`userId` ASC),
  CONSTRAINT `fk_support_amendment`
  FOREIGN KEY (`amendmentId`)
  REFERENCES `amendment` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_support_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motionSubscription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionSubscription` (
  `motionId` INT NOT NULL,
  `userId`   INT NOT NULL,
  PRIMARY KEY (`motionId`, `userId`),
  INDEX `fk_motionId` (`motionId` ASC),
  INDEX `fk_userId` (`userId` ASC),
  CONSTRAINT `fk_subscription_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subscription_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motionComment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionComment` (
  `id`                INT        NOT NULL AUTO_INCREMENT,
  `userId`            INT        NULL,
  `motionId`          INT        NULL,
  `paragraph`         SMALLINT   NULL,
  `text`              MEDIUMTEXT NOT NULL,
  `dateCreated`       TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `status`            TINYINT    NULL,
  `replyNotification` TINYINT    NULL     DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `fk_comment_userIdx` (`userId` ASC),
  INDEX `fk_comment_notion_idx` (`motionId` ASC),
  CONSTRAINT `fk_motion_comment_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `motion` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_motion_comment_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motionCommentSupporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionCommentSupporter` (
  `id`              INT      NOT NULL AUTO_INCREMENT,
  `ipHash`          CHAR(32) NULL,
  `cookieId`        INT      NULL,
  `motionCommentId` INT      NOT NULL,
  `likes`           TINYINT  NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `ip_hash_motion` (`ipHash` ASC, `motionCommentId` ASC),
  UNIQUE INDEX `cookie_motion` (`cookieId` ASC, `motionCommentId` ASC),
  INDEX `fk_motion_comment_supporter_commentIdx` (`motionCommentId` ASC),
  CONSTRAINT `fk_motion_comment_supporter_comment`
  FOREIGN KEY (`motionCommentId`)
  REFERENCES `motionComment` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `motionSupporter`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionSupporter` (
  `id`             INT                                                NOT NULL AUTO_INCREMENT,
  `motionId`       INT                                                NOT NULL,
  `position`       SMALLINT                                           NOT NULL DEFAULT 0,
  `userId`         INT                                                NOT NULL,
  `role`           ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT                                         NULL,
  `personType`     TINYINT                                            NULL,
  `name`           TEXT                                               NULL,
  `organization`   TEXT                                               NULL,
  `resolutionDate` DATE                                               NULL     DEFAULT NULL,
  `contactEmail`   VARCHAR(100)                                       NULL,
  `contactPhone`   VARCHAR(100)                                       NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_supporter_idx` (`userId` ASC),
  INDEX `fk_motionIdx` (`motionId` ASC),
  CONSTRAINT `fk_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `motion` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_supporter`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `cache`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache` (
  `id`           CHAR(32)  NOT NULL,
  `dateCreation` TIMESTAMP NULL,
  `data`         LONGBLOB  NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultationText`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationText` (
  `id`             INT         NOT NULL AUTO_INCREMENT,
  `consultationId` INT         NULL,
  `textId`         VARCHAR(20) NOT NULL,
  `text`           LONGTEXT    NULL,
  `editDate`       TIMESTAMP   NULL     DEFAULT NOW(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `consultation_text_unique` (`textId` ASC, `consultationId` ASC),
  INDEX `fk_texts_consultationIdx` (`consultationId` ASC),
  CONSTRAINT `fk_texts_consultation`
  FOREIGN KEY (`consultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultationAdmin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationAdmin` (
  `consultationId` INT NOT NULL,
  `userId`         INT NOT NULL,
  PRIMARY KEY (`consultationId`, `userId`),
  INDEX `fk_consultation_userIdx` (`userId` ASC),
  INDEX `fk_consultationIdx` (`consultationId` ASC),
  CONSTRAINT `fk_consultation_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_consultation`
  FOREIGN KEY (`consultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultationSubscription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationSubscription` (
  `consultationId` INT     NOT NULL,
  `userId`         INT     NOT NULL,
  `motions`        TINYINT NULL,
  `amendments`     TINYINT NULL,
  `comments`       TINYINT NULL,
  PRIMARY KEY (`consultationId`, `userId`),
  INDEX `fk_consultationIdx` (`consultationId` ASC),
  INDEX `fk_userIdx` (`userId` ASC),
  CONSTRAINT `fk_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_consultation`
  FOREIGN KEY (`consultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `siteAdmin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `siteAdmin` (
  `siteId` INT NOT NULL,
  `userId` INT NOT NULL,
  PRIMARY KEY (`siteId`, `userId`),
  INDEX `site_admin_fk_userIdx` (`userId` ASC),
  INDEX `site_admin_fk_siteIdx` (`siteId` ASC),
  CONSTRAINT `site_admin_fk_user`
  FOREIGN KEY (`userId`)
  REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `site_admin_fk_site`
  FOREIGN KEY (`siteId`)
  REFERENCES `site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `emailLog`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `emailLog` (
  `id`        INT          NOT NULL AUTO_INCREMENT,
  `toEmail`   VARCHAR(200) NULL,
  `toUserId`  INT          NULL     DEFAULT NULL,
  `type`      SMALLINT     NULL,
  `fromEmail` VARCHAR(200) NULL,
  `dateSent`  TIMESTAMP    NULL,
  `subject`   VARCHAR(200) NULL,
  `text`      MEDIUMTEXT   NULL,
  INDEX `fk_mail_log_userIdx` (`toUserId` ASC),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_mail_log_user`
  FOREIGN KEY (`toUserId`)
  REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultationOdtTemplate`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationOdtTemplate` (
  `id`             INT     NOT NULL AUTO_INCREMENT,
  `consultationId` INT     NOT NULL,
  `type`           TINYINT NOT NULL,
  `data`           BLOB    NOT NULL,
  INDEX `fk_consultationIdx` (`consultationId` ASC),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_odt_templates`
  FOREIGN KEY (`consultationId`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `consultationSettingsTag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationSettingsTag` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `consultationId` INT          NULL     DEFAULT NULL,
  `position`       SMALLINT     NULL,
  `title`          VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `consultation_tag_fk_consultation`
  FOREIGN KEY (`id`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `motionTag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionTag` (
  `motionId` INT NOT NULL,
  `tagId`    INT NOT NULL,
  PRIMARY KEY (`motionId`, `tagId`),
  INDEX `motion_tag_fk_tagIdx` (`tagId` ASC),
  CONSTRAINT `motion_tag_fk_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `motion_tag_fk_tag`
  FOREIGN KEY (`tagId`)
  REFERENCES `consultationSettingsTag` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `consultationSettingsMotionSection`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultationSettingsMotionSection` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `consultationId` INT          NULL     DEFAULT NULL,
  `type`           INT          NOT NULL,
  `position`       SMALLINT     NULL,
  `title`          VARCHAR(100) NOT NULL,
  `fixedWidth`     TINYINT      NOT NULL,
  `maxLen`         INT          NULL     DEFAULT NULL,
  `lineNumbers`    TINYINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `consultation_settings_motion_section_fk_consultation`
  FOREIGN KEY (`id`)
  REFERENCES `consultation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `motionSection`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `motionSection` (
  `motionId`  INT      NOT NULL,
  `sectionId` INT      NOT NULL,
  `data`      LONGTEXT NOT NULL,
  PRIMARY KEY (`motionId`, `sectionId`),
  INDEX `motion_section_fk_sectionIdx` (`sectionId` ASC),
  CONSTRAINT `motion_section_fk_motion`
  FOREIGN KEY (`motionId`)
  REFERENCES `motion` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `motion_section_fk_section`
  FOREIGN KEY (`sectionId`)
  REFERENCES `consultationSettingsMotionSection` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
  ENGINE = InnoDB;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
