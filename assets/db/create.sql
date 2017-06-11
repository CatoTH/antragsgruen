SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

--
-- Table structure for table `amendment`
--

CREATE TABLE `###TABLE_PREFIX###amendment` (
  `id`                    INT(11)     NOT NULL,
  `motionId`              INT(11)              DEFAULT NULL,
  `titlePrefix`           VARCHAR(45)          DEFAULT NULL,
  `changeEditorial`       LONGTEXT    NOT NULL,
  `changeText`            LONGTEXT    NOT NULL,
  `changeExplanation`     LONGTEXT    NOT NULL,
  `changeExplanationHtml` TINYINT(4)  NOT NULL DEFAULT '0',
  `cache`                 LONGTEXT    NOT NULL,
  `dateCreation`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePublication`       TIMESTAMP   NULL     DEFAULT NULL,
  `dateResolution`        TIMESTAMP   NULL     DEFAULT NULL,
  `status`                TINYINT(4)  NOT NULL,
  `statusString`          VARCHAR(55) NOT NULL,
  `noteInternal`          TEXT,
  `textFixed`             TINYINT(4)           DEFAULT '0',
  `globalAlternative`     TINYINT(4)           DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentAdminComment`
--

CREATE TABLE `###TABLE_PREFIX###amendmentAdminComment` (
  `id`           INT(11)    NOT NULL,
  `amendmentId`  INT(11)    NOT NULL,
  `userId`       INT(11)    NOT NULL,
  `text`         MEDIUMTEXT NOT NULL,
  `status`       TINYINT(4) NOT NULL,
  `dateCreation` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentComment`
--

CREATE TABLE `###TABLE_PREFIX###amendmentComment` (
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

CREATE TABLE `###TABLE_PREFIX###amendmentSection` (
  `amendmentId` INT(11)  NOT NULL,
  `sectionId`   INT(11)  NOT NULL,
  `data`        LONGTEXT NOT NULL,
  `dataRaw`     LONGTEXT NOT NULL,
  `cache`       LONGTEXT NOT NULL,
  `metadata`    TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentSupporter`
--

CREATE TABLE `###TABLE_PREFIX###amendmentSupporter` (
  `id`             INT(11)                                             NOT NULL,
  `amendmentId`    INT(11)                                             NOT NULL,
  `position`       SMALLINT(6)                                         NOT NULL DEFAULT '0',
  `userId`         INT(11)                                                      DEFAULT NULL,
  `role`           ENUM ('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT,
  `personType`     TINYINT(4)                                                   DEFAULT NULL,
  `name`           TEXT,
  `organization`   TEXT,
  `resolutionDate` DATE                                                         DEFAULT NULL,
  `contactName`    TEXT,
  `contactEmail`   VARCHAR(100)                                                 DEFAULT NULL,
  `contactPhone`   VARCHAR(100)                                                 DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `###TABLE_PREFIX###consultation` (
  `id`                 INT(11)      NOT NULL,
  `siteId`             INT(11)      NOT NULL,
  `urlPath`            VARCHAR(45)       DEFAULT NULL,
  `wordingBase`        VARCHAR(20)  NOT NULL,
  `title`              VARCHAR(200) NOT NULL,
  `titleShort`         VARCHAR(45)  NOT NULL,
  `eventDateFrom`      DATE              DEFAULT NULL,
  `eventDateTo`        DATE              DEFAULT NULL,
  `amendmentNumbering` TINYINT(4)   NOT NULL,
  `adminEmail`         VARCHAR(150)      DEFAULT NULL,
  `dateCreation`       TIMESTAMP    NULL DEFAULT NULL,
  `dateDeletion`       TIMESTAMP    NULL DEFAULT NULL,
  `settings`           TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationAgendaItem`
--

CREATE TABLE `###TABLE_PREFIX###consultationAgendaItem` (
  `id`             INT(11)      NOT NULL,
  `consultationId` INT(11)      NOT NULL,
  `parentItemId`   INT(11)           DEFAULT NULL,
  `position`       INT(11)      NOT NULL,
  `code`           VARCHAR(20)  NOT NULL,
  `title`          VARCHAR(250) NOT NULL,
  `description`    TEXT,
  `motionTypeId`   INT(11)           DEFAULT NULL,
  `deadline`       TIMESTAMP    NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationLog`
--

CREATE TABLE `###TABLE_PREFIX###consultationLog` (
  `id`                INT(11)     NOT NULL,
  `userId`            INT(11)              DEFAULT NULL,
  `consultationId`    INT(11)     NOT NULL,
  `actionType`        SMALLINT(6) NOT NULL,
  `actionReferenceId` INT(11)     NOT NULL,
  `actionTime`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationMotionType`
--

CREATE TABLE `###TABLE_PREFIX###consultationMotionType` (
  `id`                           INT(11)      NOT NULL,
  `consultationId`               INT(11)      NOT NULL,
  `titleSingular`                VARCHAR(100) NOT NULL,
  `titlePlural`                  VARCHAR(100) NOT NULL,
  `createTitle`                  VARCHAR(200) NOT NULL,
  `motionPrefix`                 VARCHAR(10)           DEFAULT NULL,
  `position`                     INT(11)      NOT NULL,
  `cssIcon`                      VARCHAR(100)          DEFAULT NULL,
  `pdfLayout`                    INT(11)      NOT NULL DEFAULT '0',
  `texTemplateId`                INT(11)               DEFAULT NULL,
  `deadlineMotions`              TIMESTAMP    NULL     DEFAULT NULL,
  `deadlineAmendments`           TIMESTAMP    NULL     DEFAULT NULL,
  `policyMotions`                INT(11)      NOT NULL,
  `policyAmendments`             INT(11)      NOT NULL,
  `policyComments`               INT(11)      NOT NULL,
  `policySupportMotions`         INT(11)      NOT NULL,
  `policySupportAmendments`      INT(11)      NOT NULL,
  `initiatorsCanMergeAmendments` TINYINT(4)   NOT NULL DEFAULT '0',
  `motionLikesDislikes`          INT(11)      NOT NULL,
  `amendmentLikesDislikes`       INT(11)      NOT NULL,
  `contactName`                  TINYINT(4)   NOT NULL,
  `contactEmail`                 TINYINT(4)   NOT NULL,
  `contactPhone`                 TINYINT(4)   NOT NULL,
  `supportType`                  INT(11)      NOT NULL,
  `supportTypeSettings`          TEXT,
  `amendmentMultipleParagraphs`  TINYINT(1)            DEFAULT NULL,
  `status`                       SMALLINT(6)  NOT NULL,
  `layoutTwoCols`                SMALLINT(6)           DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationOdtTemplate`
--

CREATE TABLE `###TABLE_PREFIX###consultationOdtTemplate` (
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

CREATE TABLE `###TABLE_PREFIX###consultationSettingsMotionSection` (
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
  `hasAmendments` TINYINT(4)   NOT NULL DEFAULT '1',
  `positionRight` SMALLINT(6)           DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsTag`
--

CREATE TABLE `###TABLE_PREFIX###consultationSettingsTag` (
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
-- Table structure for table `consultationText`
--

CREATE TABLE `###TABLE_PREFIX###consultationText` (
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
-- Table structure for table `consultationUserPrivilege`
--

CREATE TABLE `###TABLE_PREFIX###consultationUserPrivilege` (
  `userId`           INT(11)    NOT NULL,
  `consultationId`   INT(11)    NOT NULL,
  `privilegeView`    TINYINT(4) NOT NULL DEFAULT '0',
  `privilegeCreate`  TINYINT(4) NOT NULL DEFAULT '0',
  `adminSuper`       TINYINT(4) NOT NULL DEFAULT '0',
  `adminContentEdit` TINYINT(4) NOT NULL DEFAULT '0',
  `adminScreen`      TINYINT(4) NOT NULL DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `emailBlacklist`
--

CREATE TABLE `###TABLE_PREFIX###emailBlacklist` (
  `emailHash` VARCHAR(32) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `emailLog`
--

CREATE TABLE `###TABLE_PREFIX###emailLog` (
  `id`         INT(11)      NOT NULL,
  `fromSiteId` INT(11)           DEFAULT NULL,
  `toEmail`    VARCHAR(200)      DEFAULT NULL,
  `toUserId`   INT(11)           DEFAULT NULL,
  `type`       SMALLINT(6)       DEFAULT NULL,
  `fromEmail`  VARCHAR(200)      DEFAULT NULL,
  `dateSent`   TIMESTAMP    NULL DEFAULT NULL,
  `subject`    VARCHAR(200)      DEFAULT NULL,
  `text`       MEDIUMTEXT,
  `messageId`  VARCHAR(100) NOT NULL,
  `status`     SMALLINT(6)  NOT NULL,
  `error`      TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `migration`
--

CREATE TABLE `###TABLE_PREFIX###migration` (
  `version`    VARCHAR(180) NOT NULL,
  `apply_time` INT(11) DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motion`
--

CREATE TABLE `###TABLE_PREFIX###motion` (
  `id`              INT(11)     NOT NULL,
  `consultationId`  INT(11)     NOT NULL,
  `motionTypeId`    INT(11)     NOT NULL,
  `parentMotionId`  INT(11)              DEFAULT NULL,
  `agendaItemId`    INT(11)              DEFAULT NULL,
  `title`           TEXT        NOT NULL,
  `titlePrefix`     VARCHAR(50) NOT NULL,
  `dateCreation`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePublication` TIMESTAMP   NULL     DEFAULT NULL,
  `dateResolution`  TIMESTAMP   NULL     DEFAULT NULL,
  `status`          TINYINT(4)  NOT NULL,
  `statusString`    VARCHAR(55)          DEFAULT NULL,
  `nonAmendable`    TINYINT(4)  NOT NULL DEFAULT '0',
  `noteInternal`    TEXT,
  `cache`           LONGTEXT    NOT NULL,
  `textFixed`       TINYINT(4)           DEFAULT '0',
  `slug`            VARCHAR(100)         DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionAdminComment`
--

CREATE TABLE `###TABLE_PREFIX###motionAdminComment` (
  `id`           INT(11)    NOT NULL,
  `motionId`     INT(11)    NOT NULL,
  `userId`       INT(11)    NOT NULL,
  `text`         MEDIUMTEXT NOT NULL,
  `status`       TINYINT(4) NOT NULL,
  `dateCreation` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionComment`
--

CREATE TABLE `###TABLE_PREFIX###motionComment` (
  `id`                INT(11)     NOT NULL,
  `userId`            INT(11)              DEFAULT NULL,
  `motionId`          INT(11)     NOT NULL,
  `sectionId`         INT(11)              DEFAULT NULL,
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

CREATE TABLE `###TABLE_PREFIX###motionCommentSupporter` (
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

CREATE TABLE `###TABLE_PREFIX###motionSection` (
  `motionId`  INT(11)  NOT NULL,
  `sectionId` INT(11)  NOT NULL,
  `data`      LONGTEXT NOT NULL,
  `dataRaw`   LONGTEXT NOT NULL,
  `cache`     LONGTEXT NOT NULL,
  `metadata`  TEXT
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSubscription`
--

CREATE TABLE `###TABLE_PREFIX###motionSubscription` (
  `motionId` INT(11) NOT NULL,
  `userId`   INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSupporter`
--

CREATE TABLE `###TABLE_PREFIX###motionSupporter` (
  `id`             INT(11)                                             NOT NULL,
  `motionId`       INT(11)                                             NOT NULL,
  `position`       SMALLINT(6)                                         NOT NULL DEFAULT '0',
  `userId`         INT(11)                                                      DEFAULT NULL,
  `role`           ENUM ('initiates', 'supports', 'likes', 'dislikes') NOT NULL,
  `comment`        MEDIUMTEXT,
  `personType`     TINYINT(4)                                                   DEFAULT NULL,
  `name`           TEXT,
  `organization`   TEXT,
  `resolutionDate` DATE                                                         DEFAULT NULL,
  `contactName`    TEXT,
  `contactEmail`   VARCHAR(100)                                                 DEFAULT NULL,
  `contactPhone`   VARCHAR(100)                                                 DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionTag`
--

CREATE TABLE `###TABLE_PREFIX###motionTag` (
  `motionId` INT(11) NOT NULL,
  `tagId`    INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE `###TABLE_PREFIX###site` (
  `id`                    INT(11)      NOT NULL,
  `subdomain`             VARCHAR(45)       DEFAULT NULL,
  `title`                 VARCHAR(200) NOT NULL,
  `titleShort`            VARCHAR(100)      DEFAULT NULL,
  `dateCreation`          TIMESTAMP    NULL DEFAULT NULL,
  `dateDeletion`          TIMESTAMP    NULL DEFAULT NULL,
  `settings`              TEXT,
  `currentConsultationId` INT(11)           DEFAULT NULL,
  `public`                TINYINT(4)        DEFAULT '1',
  `contact`               MEDIUMTEXT,
  `organization`          VARCHAR(255)      DEFAULT NULL,
  `status`                SMALLINT(6)       DEFAULT '0'
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `siteAdmin`
--

CREATE TABLE `###TABLE_PREFIX###siteAdmin` (
  `siteId` INT(11) NOT NULL,
  `userId` INT(11) NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `texTemplate`
--

CREATE TABLE `###TABLE_PREFIX###texTemplate` (
  `id`         INT(11)      NOT NULL,
  `siteId`     INT(11) DEFAULT NULL,
  `title`      VARCHAR(100) NOT NULL,
  `texLayout`  TEXT         NOT NULL,
  `texContent` TEXT         NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `###TABLE_PREFIX###user` (
  `id`             INT(11)     NOT NULL,
  `name`           TEXT        NOT NULL,
  `nameGiven`      TEXT                 DEFAULT NULL,
  `nameFamily`     TEXT                 DEFAULT NULL,
  `organization`   TEXT                 DEFAULT NULL,
  `fixedData`      TINYINT(4)           DEFAULT '0',
  `email`          VARCHAR(200)         DEFAULT NULL,
  `emailConfirmed` TINYINT(4)           DEFAULT '0',
  `auth`           VARCHAR(190)         DEFAULT NULL,
  `dateCreation`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`         TINYINT(4)  NOT NULL,
  `pwdEnc`         VARCHAR(100)         DEFAULT NULL,
  `authKey`        BINARY(100) NOT NULL,
  `recoveryToken`  VARCHAR(100)         DEFAULT NULL,
  `recoveryAt`     TIMESTAMP   NULL     DEFAULT NULL,
  `emailChange`    VARCHAR(255)         DEFAULT NULL,
  `emailChangeAt`  TIMESTAMP   NULL     DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `userNotification`
--

CREATE TABLE `###TABLE_PREFIX###userNotification` (
  `id`                      INT(11)     NOT NULL,
  `userId`                  INT(11)     NOT NULL,
  `consultationId`          INT(11)          DEFAULT NULL,
  `notificationType`        SMALLINT(6) NOT NULL,
  `notificationReferenceId` INT(11)          DEFAULT NULL,
  `lastNotification`        TIMESTAMP   NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `amendmentAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentAdminComment`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `amendmentId` (`amendmentId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `amendmentComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_amendment_comment_userIdx` (`userId`),
  ADD KEY `fk_amendment_comment_amendmentIdx` (`amendmentId`);

--
-- Indexes for table `amendmentSection`
--
ALTER TABLE `###TABLE_PREFIX###amendmentSection`
  ADD PRIMARY KEY (`amendmentId`, `sectionId`),
  ADD KEY `sectionId` (`sectionId`);

--
-- Indexes for table `amendmentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###amendmentSupporter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_amendmentIdx` (`amendmentId`),
  ADD KEY `fk_supporter_idx` (`userId`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `###TABLE_PREFIX###consultation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `yii_url_UNIQUE` (`urlPath`, `siteId`),
  ADD KEY `fk_consultation_siteIdx` (`siteId`);

--
-- Indexes for table `consultationAgendaItem`
--
ALTER TABLE `###TABLE_PREFIX###consultationAgendaItem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`),
  ADD KEY `parentItemId` (`parentItemId`),
  ADD KEY `motionTypeId` (`motionTypeId`);

--
-- Indexes for table `consultationLog`
--
ALTER TABLE `###TABLE_PREFIX###consultationLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `actionTime`) USING BTREE;

--
-- Indexes for table `consultationMotionType`
--
ALTER TABLE `###TABLE_PREFIX###consultationMotionType`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`, `position`) USING BTREE,
  ADD KEY `texLayout` (`texTemplateId`);

--
-- Indexes for table `consultationOdtTemplate`
--
ALTER TABLE `###TABLE_PREFIX###consultationOdtTemplate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationSettingsMotionSection`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsMotionSection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motionType` (`motionTypeId`);

--
-- Indexes for table `consultationSettingsTag`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsTag`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultation_text_unique` (`category`, `textId`, `consultationId`),
  ADD KEY `fk_texts_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationUserPrivilege`
--
ALTER TABLE `###TABLE_PREFIX###consultationUserPrivilege`
  ADD PRIMARY KEY (`userId`, `consultationId`),
  ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `emailBlacklist`
--
ALTER TABLE `###TABLE_PREFIX###emailBlacklist`
  ADD PRIMARY KEY (`emailHash`);

--
-- Indexes for table `emailLog`
--
ALTER TABLE `###TABLE_PREFIX###emailLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mail_log_userIdx` (`toUserId`),
  ADD KEY `fromSiteId` (`fromSiteId`);

--
-- Indexes for table `migration`
--
ALTER TABLE `###TABLE_PREFIX###migration`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `motion`
--
ALTER TABLE `###TABLE_PREFIX###motion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `motionSlug` (`consultationId`, `slug`),
  ADD KEY `consultation` (`consultationId`),
  ADD KEY `parent_motion` (`parentMotionId`),
  ADD KEY `type` (`motionTypeId`),
  ADD KEY `agendaItemId` (`agendaItemId`);

--
-- Indexes for table `motionAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###motionAdminComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `motionId` (`motionId`);

--
-- Indexes for table `motionComment`
--
ALTER TABLE `###TABLE_PREFIX###motionComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_userIdx` (`userId`),
  ADD KEY `fk_comment_notion_idx` (`motionId`, `sectionId`);

--
-- Indexes for table `motionCommentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionCommentSupporter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_hash_motion` (`ipHash`, `motionCommentId`),
  ADD UNIQUE KEY `cookie_motion` (`cookieId`, `motionCommentId`),
  ADD KEY `fk_motion_comment_supporter_commentIdx` (`motionCommentId`);

--
-- Indexes for table `motionSection`
--
ALTER TABLE `###TABLE_PREFIX###motionSection`
  ADD PRIMARY KEY (`motionId`, `sectionId`),
  ADD KEY `motion_section_fk_sectionIdx` (`sectionId`);

--
-- Indexes for table `motionSubscription`
--
ALTER TABLE `###TABLE_PREFIX###motionSubscription`
  ADD PRIMARY KEY (`motionId`, `userId`),
  ADD KEY `fk_motionId` (`motionId`),
  ADD KEY `fk_userId` (`userId`);

--
-- Indexes for table `motionSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionSupporter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_supporter_idx` (`userId`),
  ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `motionTag`
--
ALTER TABLE `###TABLE_PREFIX###motionTag`
  ADD PRIMARY KEY (`motionId`, `tagId`),
  ADD KEY `motion_tag_fk_tagIdx` (`tagId`);

--
-- Indexes for table `site`
--
ALTER TABLE `###TABLE_PREFIX###site`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subdomain_UNIQUE` (`subdomain`),
  ADD KEY `fk_veranstaltungsreihe_veranstaltung1_idx` (`currentConsultationId`);

--
-- Indexes for table `siteAdmin`
--
ALTER TABLE `###TABLE_PREFIX###siteAdmin`
  ADD PRIMARY KEY (`siteId`, `userId`),
  ADD KEY `site_admin_fk_userIdx` (`userId`),
  ADD KEY `site_admin_fk_siteIdx` (`siteId`);

--
-- Indexes for table `texTemplate`
--
ALTER TABLE `###TABLE_PREFIX###texTemplate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siteId` (`siteId`);

--
-- Indexes for table `user`
--
ALTER TABLE `###TABLE_PREFIX###user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_UNIQUE` (`auth`);

--
-- Indexes for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `notificationType`, `notificationReferenceId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 278;
--
-- AUTO_INCREMENT for table `amendmentAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentAdminComment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentComment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###amendmentSupporter`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 493;
--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `###TABLE_PREFIX###consultation`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 8;
--
-- AUTO_INCREMENT for table `consultationAgendaItem`
--
ALTER TABLE `###TABLE_PREFIX###consultationAgendaItem`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 15;
--
-- AUTO_INCREMENT for table `consultationLog`
--
ALTER TABLE `###TABLE_PREFIX###consultationLog`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 11;
--
-- AUTO_INCREMENT for table `consultationMotionType`
--
ALTER TABLE `###TABLE_PREFIX###consultationMotionType`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 11;
--
-- AUTO_INCREMENT for table `consultationOdtTemplate`
--
ALTER TABLE `###TABLE_PREFIX###consultationOdtTemplate`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsMotionSection`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsMotionSection`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 33;
--
-- AUTO_INCREMENT for table `consultationSettingsTag`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsTag`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 11;
--
-- AUTO_INCREMENT for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 4;
--
-- AUTO_INCREMENT for table `emailLog`
--
ALTER TABLE `###TABLE_PREFIX###emailLog`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 9;
--
-- AUTO_INCREMENT for table `motion`
--
ALTER TABLE `###TABLE_PREFIX###motion`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 117;
--
-- AUTO_INCREMENT for table `motionAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###motionAdminComment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionComment`
--
ALTER TABLE `###TABLE_PREFIX###motionComment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionCommentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionCommentSupporter`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionSupporter`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 152;
--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `###TABLE_PREFIX###site`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 8;
--
-- AUTO_INCREMENT for table `texTemplate`
--
ALTER TABLE `###TABLE_PREFIX###texTemplate`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `###TABLE_PREFIX###user`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 5;
--
-- AUTO_INCREMENT for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  ADD CONSTRAINT `fk_ammendment_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentAdminComment`
  ADD CONSTRAINT `amendmentAdminComment_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`),
  ADD CONSTRAINT `amendmentAdminComment_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`);

--
-- Constraints for table `amendmentComment`
--
ALTER TABLE `###TABLE_PREFIX###amendmentComment`
  ADD CONSTRAINT `amendmentComment_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`),
  ADD CONSTRAINT `fk_amendment_comment_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentSection`
--
ALTER TABLE `###TABLE_PREFIX###amendmentSection`
  ADD CONSTRAINT `amendmentSection_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`),
  ADD CONSTRAINT `amendmentSection_ibfk_2` FOREIGN KEY (`sectionId`) REFERENCES `###TABLE_PREFIX###consultationSettingsMotionSection` (`id`);

--
-- Constraints for table `amendmentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###amendmentSupporter`
  ADD CONSTRAINT `fk_support_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_support_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultation`
--
ALTER TABLE `###TABLE_PREFIX###consultation`
  ADD CONSTRAINT `fk_veranstaltung_veranstaltungsreihe1` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationAgendaItem`
--
ALTER TABLE `###TABLE_PREFIX###consultationAgendaItem`
  ADD CONSTRAINT `consultationAgendaItem_ibfk_1` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `consultationAgendaItem_ibfk_2` FOREIGN KEY (`parentItemId`) REFERENCES `###TABLE_PREFIX###consultationAgendaItem` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `consultationAgendaItem_ibfk_3` FOREIGN KEY (`motionTypeId`) REFERENCES `###TABLE_PREFIX###consultationMotionType` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationLog`
--
ALTER TABLE `###TABLE_PREFIX###consultationLog`
  ADD CONSTRAINT `consultationLog_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `consultationLog_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);

--
-- Constraints for table `consultationMotionType`
--
ALTER TABLE `###TABLE_PREFIX###consultationMotionType`
  ADD CONSTRAINT `consultationMotionType_ibfk_1` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
  ADD CONSTRAINT `consultationMotionType_ibfk_2` FOREIGN KEY (`texTemplateId`) REFERENCES `###TABLE_PREFIX###texTemplate` (`id`);

--
-- Constraints for table `consultationOdtTemplate`
--
ALTER TABLE `###TABLE_PREFIX###consultationOdtTemplate`
  ADD CONSTRAINT `fk_odt_templates` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsMotionSection`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsMotionSection`
  ADD CONSTRAINT `consultationSettingsMotionSection_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `###TABLE_PREFIX###consultationMotionType` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsTag`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsTag`
  ADD CONSTRAINT `consultation_tag_fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  ADD CONSTRAINT `fk_texts_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationUserPrivilege`
--
ALTER TABLE `###TABLE_PREFIX###consultationUserPrivilege`
  ADD CONSTRAINT `consultationUserPrivilege_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `consultationUserPrivilege_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);

--
-- Constraints for table `emailLog`
--
ALTER TABLE `###TABLE_PREFIX###emailLog`
  ADD CONSTRAINT `emailLog_ibfk_1` FOREIGN KEY (`fromSiteId`) REFERENCES `###TABLE_PREFIX###site` (`id`),
  ADD CONSTRAINT `fk_mail_log_user` FOREIGN KEY (`toUserId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motion`
--
ALTER TABLE `###TABLE_PREFIX###motion`
  ADD CONSTRAINT `fk_motion_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_site_parent` FOREIGN KEY (`parentMotionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `motion_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `###TABLE_PREFIX###consultationMotionType` (`id`),
  ADD CONSTRAINT `motion_ibfk_2` FOREIGN KEY (`agendaItemId`) REFERENCES `###TABLE_PREFIX###consultationAgendaItem` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionAdminComment`
--
ALTER TABLE `###TABLE_PREFIX###motionAdminComment`
  ADD CONSTRAINT `motionAdminComment_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `motionAdminComment_ibfk_2` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`);

--
-- Constraints for table `motionComment`
--
ALTER TABLE `###TABLE_PREFIX###motionComment`
  ADD CONSTRAINT `fk_motion_comment_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `motionComment_ibfk_1` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`),
  ADD CONSTRAINT `motionComment_ibfk_2` FOREIGN KEY (`motionId`, `sectionId`) REFERENCES `###TABLE_PREFIX###motionSection` (`motionId`, `sectionId`);

--
-- Constraints for table `motionCommentSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionCommentSupporter`
  ADD CONSTRAINT `fk_motion_comment_supporter_comment` FOREIGN KEY (`motionCommentId`) REFERENCES `###TABLE_PREFIX###motionComment` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSection`
--
ALTER TABLE `###TABLE_PREFIX###motionSection`
  ADD CONSTRAINT `motion_section_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `motion_section_fk_section` FOREIGN KEY (`sectionId`) REFERENCES `###TABLE_PREFIX###consultationSettingsMotionSection` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSubscription`
--
ALTER TABLE `###TABLE_PREFIX###motionSubscription`
  ADD CONSTRAINT `fk_subscription_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_subscription_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionSupporter`
--
ALTER TABLE `###TABLE_PREFIX###motionSupporter`
  ADD CONSTRAINT `fk_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_supporter` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

--
-- Constraints for table `motionTag`
--
ALTER TABLE `###TABLE_PREFIX###motionTag`
  ADD CONSTRAINT `motion_tag_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `motion_tag_fk_tag` FOREIGN KEY (`tagId`) REFERENCES `###TABLE_PREFIX###consultationSettingsTag` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `site`
--
ALTER TABLE `###TABLE_PREFIX###site`
  ADD CONSTRAINT `fk_site_consultation` FOREIGN KEY (`currentConsultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `siteAdmin`
--
ALTER TABLE `###TABLE_PREFIX###siteAdmin`
  ADD CONSTRAINT `site_admin_fk_site` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `site_admin_fk_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `texTemplate`
--
ALTER TABLE `###TABLE_PREFIX###texTemplate`
  ADD CONSTRAINT `texTemplate_ibfk_1` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`);

--
-- Constraints for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  ADD CONSTRAINT `userNotification_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `userNotification_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
