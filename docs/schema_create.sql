SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';


--
-- Table structure for table `amendment`
--

CREATE TABLE `amendment` (
  `id`                    INT(11)     NOT NULL,
  `motionId`              INT(11)              DEFAULT NULL,
  `titlePrefix`           VARCHAR(45)          DEFAULT NULL,
  `changeMetatext`        LONGTEXT    NOT NULL,
  `changeText`            LONGTEXT    NOT NULL,
  `changeExplanation`     LONGTEXT    NOT NULL,
  `changeExplanationHtml` TINYINT(4)  NOT NULL DEFAULT '0',
  `cache`                 TEXT        NOT NULL,
  `dateCreation`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateResolution`        TIMESTAMP   NULL     DEFAULT NULL,
  `status`                TINYINT(4)  NOT NULL,
  `statusString`          VARCHAR(55) NOT NULL,
  `noteInternal`          TEXT,
  `textFixed`             TINYINT(4)           DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentComment`
--

CREATE TABLE `amendmentComment` (
  `id`                INT(11)     NOT NULL,
  `userId`            INT(11)              DEFAULT NULL,
  `amendmentId`       INT(11)     NOT NULL,
  `paragraph`         SMALLINT(6) NOT NULL,
  `text`              MEDIUMTEXT  NOT NULL,
  `name`              TEXT        NOT NULL,
  `contactEmail`      VARCHAR(100)         DEFAULT NULL,
  `dateCreation`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`            TINYINT(4)  NOT NULL,
  `replyNotification` TINYINT(4)  NOT NULL DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentSection`
--

CREATE TABLE `amendmentSection` (
  `amendmentId` INT(11)  NOT NULL,
  `sectionId`   INT(11)  NOT NULL,
  `data`        LONGTEXT NOT NULL,
  `dataRaw`     LONGTEXT NOT NULL,
  `metadata`    TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentSupporter`
--

CREATE TABLE `amendmentSupporter` (
  `id`             INT(11)                                            NOT NULL,
  `amendmentId`    INT(11)                                            NOT NULL,
  `position`       SMALLINT(6)                                        NOT NULL DEFAULT '0',
  `userId`         INT(11)                                                     DEFAULT NULL,
  `role`           ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT,
  `personType`     TINYINT(4)                                                  DEFAULT NULL,
  `name`           TEXT,
  `organization`   TEXT,
  `resolutionDate` DATE                                                        DEFAULT NULL,
  `contactEmail`   VARCHAR(100)                                                DEFAULT NULL,
  `contactPhone`   VARCHAR(100)                                                DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `id`           CHAR(32)  NOT NULL,
  `dateCreation` TIMESTAMP NULL DEFAULT NULL,
  `data`         LONGBLOB
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `consultation` (
  `id`                 INT(11)      NOT NULL,
  `siteId`             INT(11)      NOT NULL,
  `urlPath`            VARCHAR(45)  DEFAULT NULL,
  `type`               TINYINT(4)   DEFAULT NULL,
  `wordingBase`        VARCHAR(20)  NOT NULL,
  `title`              VARCHAR(200) NOT NULL,
  `titleShort`         VARCHAR(45)  NOT NULL,
  `eventDateFrom`      DATE         DEFAULT NULL,
  `eventDateTo`        DATE         DEFAULT NULL,
  `amendmentNumbering` TINYINT(4)   NOT NULL,
  `adminEmail`         VARCHAR(150) DEFAULT NULL,
  `settings`           TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationAdmin`
--

CREATE TABLE `consultationAdmin` (
  `consultationId` INT(11) NOT NULL,
  `userId`         INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationAgendaItem`
--

CREATE TABLE `consultationAgendaItem` (
  `id`             INT(11)      NOT NULL,
  `consultationId` INT(11)      NOT NULL,
  `parentItemId`   INT(11)           DEFAULT NULL,
  `position`       INT(11)      NOT NULL,
  `code`           VARCHAR(20)  NOT NULL,
  `codeExplicit`   VARCHAR(20)  NOT NULL,
  `title`          VARCHAR(250) NOT NULL,
  `description`    TEXT         NOT NULL,
  `motionTypeId`   INT(11)           DEFAULT NULL,
  `deadline`       TIMESTAMP    NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationMotionType`
--

CREATE TABLE `consultationMotionType` (
  `id`                 INT(11)      NOT NULL,
  `consultationId`     INT(11)      NOT NULL,
  `titleSingular`      VARCHAR(100) NOT NULL,
  `titlePlural`        VARCHAR(100) NOT NULL,
  `motionPrefix`       VARCHAR(10)       DEFAULT NULL,
  `position`           INT(11)      NOT NULL,
  `cssicon`            VARCHAR(100)      DEFAULT NULL,
  `deadlineMotions`    TIMESTAMP    NULL DEFAULT NULL,
  `deadlineAmendments` TIMESTAMP    NULL DEFAULT NULL,
  `policyMotions`      INT(11)      NOT NULL,
  `policyAmendments`   INT(11)      NOT NULL,
  `policyComments`     INT(11)      NOT NULL,
  `policySupport`      INT(11)      NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationOdtTemplate`
--

CREATE TABLE `consultationOdtTemplate` (
  `id`             INT(11)    NOT NULL,
  `consultationId` INT(11)    NOT NULL,
  `type`           TINYINT(4) NOT NULL,
  `data`           BLOB       NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsMotionSection`
--

CREATE TABLE `consultationSettingsMotionSection` (
  `id`            INT(11)      NOT NULL,
  `motionTypeId`  INT(11)      NOT NULL,
  `type`          INT(11)      NOT NULL,
  `position`      SMALLINT(6)           DEFAULT NULL,
  `status`        TINYINT(4)   NOT NULL,
  `title`         VARCHAR(100) NOT NULL,
  `data`          TEXT,
  `fixedWidth`    TINYINT(4)   NOT NULL,
  `required`      TINYINT(4)   NOT NULL,
  `maxLen`        INT(11)               DEFAULT NULL,
  `lineNumbers`   TINYINT(4)   NOT NULL DEFAULT '0',
  `hasComments`   TINYINT(4)   NOT NULL,
  `hasAmendments` TINYINT(4)   NOT NULL DEFAULT '1'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsTag`
--

CREATE TABLE `consultationSettingsTag` (
  `id`             INT(11)      NOT NULL,
  `consultationId` INT(11)     DEFAULT NULL,
  `position`       SMALLINT(6) DEFAULT NULL,
  `title`          VARCHAR(100) NOT NULL,
  `cssicon`        SMALLINT(6) DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSubscription`
--

CREATE TABLE `consultationSubscription` (
  `consultationId` INT(11) NOT NULL,
  `userId`         INT(11) NOT NULL,
  `motions`        TINYINT(4) DEFAULT NULL,
  `amendments`     TINYINT(4) DEFAULT NULL,
  `comments`       TINYINT(4) DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationText`
--

CREATE TABLE `consultationText` (
  `id`             INT(11)      NOT NULL,
  `consultationId` INT(11)           DEFAULT NULL,
  `category`       VARCHAR(20)  NOT NULL,
  `textId`         VARCHAR(100) NOT NULL,
  `text`           LONGTEXT,
  `editDate`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `emailLog`
--

CREATE TABLE `emailLog` (
  `id`        INT(11)   NOT NULL,
  `toEmail`   VARCHAR(200)   DEFAULT NULL,
  `toUserId`  INT(11)        DEFAULT NULL,
  `type`      SMALLINT(6)    DEFAULT NULL,
  `fromEmail` VARCHAR(200)   DEFAULT NULL,
  `dateSent`  TIMESTAMP NULL DEFAULT NULL,
  `subject`   VARCHAR(200)   DEFAULT NULL,
  `text`      MEDIUMTEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motion`
--

CREATE TABLE `motion` (
  `id`             INT(11)     NOT NULL,
  `consultationId` INT(11)     NOT NULL,
  `motionTypeId`   INT(11)     NOT NULL,
  `parentMotionId` INT(11)              DEFAULT NULL,
  `agendaItemId`   INT(11)              DEFAULT NULL,
  `title`          TEXT        NOT NULL,
  `titlePrefix`    VARCHAR(50) NOT NULL,
  `dateCreation`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateResolution` VARCHAR(45)          DEFAULT NULL,
  `status`         TINYINT(4)  NOT NULL,
  `statusString`   VARCHAR(55)          DEFAULT NULL,
  `noteInternal`   TEXT,
  `cache`          TEXT        NOT NULL,
  `textFixed`      TINYINT(4)           DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionComment`
--

CREATE TABLE `motionComment` (
  `id`                INT(11)     NOT NULL,
  `userId`            INT(11)              DEFAULT NULL,
  `motionId`          INT(11)     NOT NULL,
  `sectionId`         INT(11)     NOT NULL,
  `paragraph`         SMALLINT(6) NOT NULL,
  `text`              MEDIUMTEXT  NOT NULL,
  `name`              TEXT        NOT NULL,
  `contactEmail`      VARCHAR(100)         DEFAULT NULL,
  `dateCreation`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`            TINYINT(4)  NOT NULL,
  `replyNotification` TINYINT(4)  NOT NULL DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionCommentSupporter`
--

CREATE TABLE `motionCommentSupporter` (
  `id`              INT(11) NOT NULL,
  `ipHash`          CHAR(32)   DEFAULT NULL,
  `cookieId`        INT(11)    DEFAULT NULL,
  `motionCommentId` INT(11) NOT NULL,
  `likes`           TINYINT(4) DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSection`
--

CREATE TABLE `motionSection` (
  `motionId`  INT(11)  NOT NULL,
  `sectionId` INT(11)  NOT NULL,
  `data`      LONGTEXT NOT NULL,
  `metadata`  TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSubscription`
--

CREATE TABLE `motionSubscription` (
  `motionId` INT(11) NOT NULL,
  `userId`   INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSupporter`
--

CREATE TABLE `motionSupporter` (
  `id`             INT(11)                                            NOT NULL,
  `motionId`       INT(11)                                            NOT NULL,
  `position`       SMALLINT(6)                                        NOT NULL DEFAULT '0',
  `userId`         INT(11)                                                     DEFAULT NULL,
  `role`           ENUM('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT,
  `personType`     TINYINT(4)                                                  DEFAULT NULL,
  `name`           TEXT,
  `organization`   TEXT,
  `resolutionDate` DATE                                                        DEFAULT NULL,
  `contactEmail`   VARCHAR(100)                                                DEFAULT NULL,
  `contactPhone`   VARCHAR(100)                                                DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionTag`
--

CREATE TABLE `motionTag` (
  `motionId` INT(11) NOT NULL,
  `tagId`    INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE `site` (
  `id`                    INT(11)      NOT NULL,
  `subdomain`             VARCHAR(45)  NOT NULL,
  `title`                 VARCHAR(200) NOT NULL,
  `titleShort`            VARCHAR(100) DEFAULT NULL,
  `settings`              TEXT,
  `currentConsultationId` INT(11)      DEFAULT NULL,
  `public`                TINYINT(4)   DEFAULT '1',
  `contact`               MEDIUMTEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `siteAdmin`
--

CREATE TABLE `siteAdmin` (
  `siteId` INT(11) NOT NULL,
  `userId` INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id`              INT(11)    NOT NULL,
  `name`            TEXT       NOT NULL,
  `email`           VARCHAR(200)        DEFAULT NULL,
  `emailConfirmed`  TINYINT(4)          DEFAULT '0',
  `auth`            VARCHAR(190)        DEFAULT NULL,
  `dateCreation`    TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          TINYINT(4) NOT NULL,
  `pwdEnc`          VARCHAR(100)        DEFAULT NULL,
  `authKey`         BINARY(100)         DEFAULT NULL,
  `siteNamespaceId` INT(11)             DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amendment`
--
ALTER TABLE `amendment`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_amendment_comment_userIdx` (`userId`),
ADD KEY `fk_amendment_comment_amendmentIdx` (`amendmentId`);

--
-- Indexes for table `amendmentSection`
--
ALTER TABLE `amendmentSection`
ADD PRIMARY KEY (`amendmentId`, `sectionId`),
ADD KEY `sectionId` (`sectionId`);

--
-- Indexes for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_amendmentIdx` (`amendmentId`),
ADD KEY `fk_supporter_idx` (`userId`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `yii_url_UNIQUE` (`urlPath`, `siteId`),
ADD KEY `fk_consultation_siteIdx` (`siteId`);

--
-- Indexes for table `consultationAdmin`
--
ALTER TABLE `consultationAdmin`
ADD PRIMARY KEY (`consultationId`, `userId`),
ADD KEY `fk_consultation_userIdx` (`userId`),
ADD KEY `fk_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationAgendaItem`
--
ALTER TABLE `consultationAgendaItem`
ADD PRIMARY KEY (`id`),
ADD KEY `consultationId` (`consultationId`),
ADD KEY `parentItemId` (`parentItemId`),
ADD KEY `motionTypeId` (`motionTypeId`);

--
-- Indexes for table `consultationMotionType`
--
ALTER TABLE `consultationMotionType`
ADD PRIMARY KEY (`id`),
ADD KEY `consultationId` (`consultationId`, `position`) USING BTREE;

--
-- Indexes for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
ADD PRIMARY KEY (`id`),
ADD KEY `motionType` (`motionTypeId`);

--
-- Indexes for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
ADD PRIMARY KEY (`id`),
ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `consultationSubscription`
--
ALTER TABLE `consultationSubscription`
ADD PRIMARY KEY (`consultationId`, `userId`),
ADD KEY `fk_consultationIdx` (`consultationId`),
ADD KEY `fk_userIdx` (`userId`);

--
-- Indexes for table `consultationText`
--
ALTER TABLE `consultationText`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `consultation_text_unique` (`category`, `textId`, `consultationId`),
ADD KEY `fk_texts_consultationIdx` (`consultationId`);

--
-- Indexes for table `emailLog`
--
ALTER TABLE `emailLog`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_mail_log_userIdx` (`toUserId`);

--
-- Indexes for table `motion`
--
ALTER TABLE `motion`
ADD PRIMARY KEY (`id`),
ADD KEY `consultation` (`consultationId`),
ADD KEY `parent_motion` (`parentMotionId`),
ADD KEY `type` (`motionTypeId`),
ADD KEY `agendaItemId` (`agendaItemId`);

--
-- Indexes for table `motionComment`
--
ALTER TABLE `motionComment`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_comment_userIdx` (`userId`),
ADD KEY `fk_comment_notion_idx` (`motionId`, `sectionId`);

--
-- Indexes for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `ip_hash_motion` (`ipHash`, `motionCommentId`),
ADD UNIQUE KEY `cookie_motion` (`cookieId`, `motionCommentId`),
ADD KEY `fk_motion_comment_supporter_commentIdx` (`motionCommentId`);

--
-- Indexes for table `motionSection`
--
ALTER TABLE `motionSection`
ADD PRIMARY KEY (`motionId`, `sectionId`),
ADD KEY `motion_section_fk_sectionIdx` (`sectionId`);

--
-- Indexes for table `motionSubscription`
--
ALTER TABLE `motionSubscription`
ADD PRIMARY KEY (`motionId`, `userId`),
ADD KEY `fk_motionId` (`motionId`),
ADD KEY `fk_userId` (`userId`);

--
-- Indexes for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_supporter_idx` (`userId`),
ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `motionTag`
--
ALTER TABLE `motionTag`
ADD PRIMARY KEY (`motionId`, `tagId`),
ADD KEY `motion_tag_fk_tagIdx` (`tagId`);

--
-- Indexes for table `site`
--
ALTER TABLE `site`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `subdomain_UNIQUE` (`subdomain`),
ADD KEY `fk_veranstaltungsreihe_veranstaltung1_idx` (`currentConsultationId`);

--
-- Indexes for table `siteAdmin`
--
ALTER TABLE `siteAdmin`
ADD PRIMARY KEY (`siteId`, `userId`),
ADD KEY `site_admin_fk_userIdx` (`userId`),
ADD KEY `site_admin_fk_siteIdx` (`siteId`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `auth_UNIQUE` (`auth`),
ADD KEY `fk_user_namespaceIdx` (`siteNamespaceId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amendment`
--
ALTER TABLE `amendment`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `consultation`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationAgendaItem`
--
ALTER TABLE `consultationAgendaItem`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationMotionType`
--
ALTER TABLE `consultationMotionType`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationText`
--
ALTER TABLE `consultationText`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `emailLog`
--
ALTER TABLE `emailLog`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motion`
--
ALTER TABLE `motion`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionComment`
--
ALTER TABLE `motionComment`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `site`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `amendment`
--
ALTER TABLE `amendment`
ADD CONSTRAINT `fk_ammendment_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
ADD CONSTRAINT `amendmentComment_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`),
ADD CONSTRAINT `fk_amendment_comment_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentSection`
--
ALTER TABLE `amendmentSection`
ADD CONSTRAINT `amendmentSection_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`),
ADD CONSTRAINT `amendmentSection_ibfk_2` FOREIGN KEY (`sectionId`) REFERENCES `consultationSettingsMotionSection` (`id`);

--
-- Constraints for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
ADD CONSTRAINT `fk_support_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultation`
--
ALTER TABLE `consultation`
ADD CONSTRAINT `fk_veranstaltung_veranstaltungsreihe1` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationAdmin`
--
ALTER TABLE `consultationAdmin`
ADD CONSTRAINT `fk_consultation_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationAgendaItem`
--
ALTER TABLE `consultationAgendaItem`
ADD CONSTRAINT `consultationAgendaItem_ibfk_1` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `consultationAgendaItem_ibfk_2` FOREIGN KEY (`parentItemId`) REFERENCES `consultationAgendaItem` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION,
ADD CONSTRAINT `consultationAgendaItem_ibfk_3` FOREIGN KEY (`motionTypeId`) REFERENCES `consultationMotionType` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationMotionType`
--
ALTER TABLE `consultationMotionType`
ADD CONSTRAINT `consultationMotionType_ibfk_1` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);

--
-- Constraints for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
ADD CONSTRAINT `fk_odt_templates` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
ADD CONSTRAINT `consultationSettingsMotionSection_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `consultationMotionType` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
ADD CONSTRAINT `consultation_tag_fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSubscription`
--
ALTER TABLE `consultationSubscription`
ADD CONSTRAINT `fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationText`
--
ALTER TABLE `consultationText`
ADD CONSTRAINT `fk_texts_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `emailLog`
--
ALTER TABLE `emailLog`
ADD CONSTRAINT `fk_mail_log_user` FOREIGN KEY (`toUserId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motion`
--
ALTER TABLE `motion`
ADD CONSTRAINT `fk_motion_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_site_parent` FOREIGN KEY (`parentMotionId`) REFERENCES `motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `motion_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `consultationMotionType` (`id`),
ADD CONSTRAINT `motion_ibfk_2` FOREIGN KEY (`agendaItemId`) REFERENCES `consultationAgendaItem` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionComment`
--
ALTER TABLE `motionComment`
ADD CONSTRAINT `fk_motion_comment_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
ADD CONSTRAINT `motionComment_ibfk_1` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`),
ADD CONSTRAINT `motionComment_ibfk_2` FOREIGN KEY (`motionId`, `sectionId`) REFERENCES `motionSection` (`motionId`, `sectionId`);

--
-- Constraints for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
ADD CONSTRAINT `fk_motion_comment_supporter_comment` FOREIGN KEY (`motionCommentId`) REFERENCES `motionComment` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSection`
--
ALTER TABLE `motionSection`
ADD CONSTRAINT `motion_section_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `motion_section_fk_section` FOREIGN KEY (`sectionId`) REFERENCES `consultationSettingsMotionSection` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSubscription`
--
ALTER TABLE `motionSubscription`
ADD CONSTRAINT `fk_subscription_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_subscription_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
ADD CONSTRAINT `fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_supporter` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionTag`
--
ALTER TABLE `motionTag`
ADD CONSTRAINT `motion_tag_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `motion_tag_fk_tag` FOREIGN KEY (`tagId`) REFERENCES `consultationSettingsTag` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `site`
--
ALTER TABLE `site`
ADD CONSTRAINT `fk_site_consultation` FOREIGN KEY (`currentConsultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `siteAdmin`
--
ALTER TABLE `siteAdmin`
ADD CONSTRAINT `site_admin_fk_site` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
ADD CONSTRAINT `site_admin_fk_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
ADD CONSTRAINT `fk_user_namespace` FOREIGN KEY (`siteNamespaceId`) REFERENCES `site` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
