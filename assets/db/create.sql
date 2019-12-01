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
  `globalAlternative`     TINYINT(4)           DEFAULT '0',
  `proposalStatus`        TINYINT(4)           DEFAULT NULL,
  `proposalReferenceId`   INT(11)              DEFAULT NULL,
  `proposalComment`       TEXT,
  `proposalVisibleFrom`   TIMESTAMP   NULL     DEFAULT NULL,
  `proposalNotification`  TIMESTAMP   NULL     DEFAULT NULL,
  `proposalUserStatus`    TINYINT(4)  NULL     DEFAULT NULL,
  `proposalExplanation`   TEXT,
  `votingStatus`          TINYINT(4)           DEFAULT NULL,
  `votingBlockId`         INT(11)              DEFAULT NULL,
  `votingData`            TEXT                 DEFAULT NULL,
  `responsibilityId`      INT(11)              DEFAULT NULL,
  `responsibilityComment` TEXT                 DEFAULT NULL
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
  `parentCommentId`   INT(11)              DEFAULT NULL,
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
  `contactPhone`   VARCHAR(100)                                                 DEFAULT NULL,
  `dateCreation`   TIMESTAMP NOT NULL,
  `extraData`      TEXT DEFAULT NULL
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
-- Table structure for table `consultationFile`
--

CREATE TABLE `###TABLE_PREFIX###consultationFile` (
  `id`             int(11)      NOT NULL,
  `consultationId` int(11)               DEFAULT NULL,
  `siteId`         int(11)               DEFAULT NULL,
  `filename`       varchar(250) NOT NULL,
  `filesize`       int(11)      NOT NULL,
  `mimetype`       varchar(250) NOT NULL,
  `width`          int(11)               DEFAULT NULL,
  `height`         int(11)               DEFAULT NULL,
  `data`           mediumblob   NOT NULL,
  `dataHash`       varchar(40)  NOT NULL,
  `dateCreation`   timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
  ON UPDATE CURRENT_TIMESTAMP
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
  `id`                           int(11)      NOT NULL,
  `consultationId`               int(11)      NOT NULL,
  `titleSingular`                varchar(100) NOT NULL,
  `titlePlural`                  varchar(100) NOT NULL,
  `createTitle`                  varchar(200) NOT NULL,
  `sidebarCreateButton`          tinyint(4)   NOT NULL DEFAULT '1',
  `motionPrefix`                 varchar(10)           DEFAULT NULL,
  `position`                     int(11)      NOT NULL,
  `settings`                     text DEFAULT NULL,
  `cssIcon`                      varchar(100)          DEFAULT NULL,
  `pdfLayout`                    int(11)      NOT NULL DEFAULT '0',
  `texTemplateId`                int(11)               DEFAULT NULL,
  `deadlines`                    text,
  `policyMotions`                int(11)      NOT NULL,
  `policyAmendments`             int(11)      NOT NULL,
  `policyComments`               int(11)      NOT NULL,
  `policySupportMotions`         int(11)      NOT NULL,
  `policySupportAmendments`      int(11)      NOT NULL,
  `initiatorsCanMergeAmendments` tinyint(4)   NOT NULL DEFAULT '0',
  `motionLikesDislikes`          int(11)      NOT NULL,
  `amendmentLikesDislikes`       int(11)      NOT NULL,
  `supportType`                  int(11)      NOT NULL,
  `supportTypeSettings`          text,
  `supportTypeMotions`           text NULL DEFAULT NULL,
  `supportTypeAmendments`        text NULL DEFAULT NULL,
  `amendmentMultipleParagraphs`  tinyint(1)            DEFAULT NULL,
  `status`                       smallint(6)  NOT NULL,
  `layoutTwoCols`                smallint(6)           DEFAULT '0'
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
  `positionRight` SMALLINT(6)           DEFAULT '0',
  `printTitle`    TINYINT(4)   NOT NULL DEFAULT '1'
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
  `id`             int(11)      NOT NULL,
  `consultationId` int(11)           DEFAULT NULL,
  `siteId`         int(11)           DEFAULT NULL,
  `category`       varchar(20)  NOT NULL,
  `textId`         varchar(100) NOT NULL,
  `menuPosition`   int(11)           DEFAULT NULL,
  `title`          text              DEFAULT NULL,
  `breadcrumb`     text              DEFAULT NULL,
  `text`           longtext,
  `editDate`       timestamp    NULL DEFAULT CURRENT_TIMESTAMP
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
  `adminScreen`      TINYINT(4) NOT NULL DEFAULT '0',
  `adminProposals`   TINYINT(4) NOT NULL DEFAULT '0'
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
  `id`                    INT(11)     NOT NULL,
  `consultationId`        INT(11)     NOT NULL,
  `motionTypeId`          INT(11)     NOT NULL,
  `parentMotionId`        INT(11)              DEFAULT NULL,
  `agendaItemId`          INT(11)              DEFAULT NULL,
  `title`                 TEXT        NOT NULL,
  `titlePrefix`           VARCHAR(50) NOT NULL,
  `dateCreation`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePublication`       TIMESTAMP   NULL     DEFAULT NULL,
  `dateResolution`        TIMESTAMP   NULL     DEFAULT NULL,
  `status`                TINYINT(4)  NOT NULL,
  `statusString`          VARCHAR(55)          DEFAULT NULL,
  `nonAmendable`          TINYINT(4)  NOT NULL DEFAULT '0',
  `noteInternal`          TEXT,
  `cache`                 LONGTEXT    NOT NULL,
  `textFixed`             TINYINT(4)           DEFAULT '0',
  `slug`                  VARCHAR(100)         DEFAULT NULL,
  `proposalStatus`        TINYINT(4)           DEFAULT NULL,
  `proposalReferenceId`   INT(11)              DEFAULT NULL,
  `proposalComment`       TEXT,
  `proposalVisibleFrom`   TIMESTAMP   NULL     DEFAULT NULL,
  `proposalNotification`  TIMESTAMP   NULL     DEFAULT NULL,
  `proposalUserStatus`    TINYINT(4)  NULL     DEFAULT NULL,
  `proposalExplanation`   TEXT,
  `votingStatus`          TINYINT(4)           DEFAULT NULL,
  `votingBlockId`         INT(11)              DEFAULT NULL,
  `votingData`            TEXT                 DEFAULT NULL,
  `responsibilityId`      INT(11)              DEFAULT NULL,
  `responsibilityComment` TEXT                 DEFAULT NULL
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
  `parentCommentId`   INT(11)              DEFAULT NULL,
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
  `contactPhone`   VARCHAR(100)                                                 DEFAULT NULL,
  `dateCreation`   TIMESTAMP NOT NULL,
  `extraData`      TEXT DEFAULT NULL
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
  `id`              INT(11)     NOT NULL,
  `name`            TEXT        NOT NULL,
  `nameGiven`       TEXT,
  `nameFamily`      TEXT,
  `organization`    TEXT,
  `organizationIds` TEXT,
  `fixedData`       TINYINT(4)           DEFAULT '0',
  `email`           VARCHAR(200)         DEFAULT NULL,
  `emailConfirmed`  TINYINT(4)           DEFAULT '0',
  `auth`            VARCHAR(190)         DEFAULT NULL,
  `dateCreation`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          TINYINT(4)  NOT NULL,
  `pwdEnc`          VARCHAR(100)         DEFAULT NULL,
  `authKey`         BINARY(100) NOT NULL,
  `recoveryToken`   VARCHAR(100)         DEFAULT NULL,
  `recoveryAt`      TIMESTAMP   NULL     DEFAULT NULL,
  `emailChange`     VARCHAR(255)         DEFAULT NULL,
  `emailChangeAt`   TIMESTAMP   NULL     DEFAULT NULL
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
  `settings`                TEXT             DEFAULT NULL,
  `lastNotification`        TIMESTAMP   NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `votingBlock`
--

CREATE TABLE `###TABLE_PREFIX###votingBlock` (
  `id`             INT(11)      NOT NULL,
  `consultationId` INT(11)      NOT NULL,
  `title`          VARCHAR(150) NOT NULL,
  `votingStatus`   TINYINT(4) DEFAULT NULL
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
  ADD KEY `fk_motionIdx` (`motionId`),
  ADD KEY `amendment_reference_am` (`proposalReferenceId`),
  ADD KEY `ix_amendment_voting_block` (`votingBlockId`),
  ADD KEY `fk_amendment_responsibility` (`responsibilityId`);

--
-- Indexes for table `amendmentAdminComment`
--
ALTER TABLE `amendmentAdminComment`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `amendmentId` (`amendmentId`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_amendment_comment_userIdx` (`userId`),
  ADD KEY `fk_amendment_comment_amendmentIdx` (`amendmentId`),
  ADD KEY `fk_amendment_comment_parents` (`parentCommentId`);

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
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `yii_url_UNIQUE` (`urlPath`, `siteId`),
  ADD KEY `fk_consultation_siteIdx` (`siteId`);

--
-- Indexes for table `consultationAgendaItem`
--
ALTER TABLE `consultationAgendaItem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`),
  ADD KEY `parentItemId` (`parentItemId`),
  ADD KEY `motionTypeId` (`motionTypeId`);

--
-- Indexes for table `consultationFile`
--

ALTER TABLE `consultationFile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_file_consultation` (`consultationId`),
  ADD KEY `consultation_file_site` (`siteId`);

--
-- Indexes for table `consultationLog`
--
ALTER TABLE `consultationLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `actionTime`) USING BTREE;

--
-- Indexes for table `consultationMotionType`
--
ALTER TABLE `consultationMotionType`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`, `position`) USING BTREE,
  ADD KEY `texLayout` (`texTemplateId`);

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
-- Indexes for table `consultationText`
--
ALTER TABLE `consultationText`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultation_text_unique` (`category`, `textId`, `consultationId`),
  ADD KEY `fk_texts_consultationIdx` (`consultationId`),
  ADD KEY `consultation_text_site` (`siteId`);

--
-- Indexes for table `consultationUserPrivilege`
--
ALTER TABLE `consultationUserPrivilege`
  ADD PRIMARY KEY (`userId`, `consultationId`),
  ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `emailBlacklist`
--
ALTER TABLE `emailBlacklist`
  ADD PRIMARY KEY (`emailHash`);

--
-- Indexes for table `emailLog`
--
ALTER TABLE `emailLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mail_log_userIdx` (`toUserId`),
  ADD KEY `fromSiteId` (`fromSiteId`);

--
-- Indexes for table `migration`
--
ALTER TABLE `migration`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `motion`
--
ALTER TABLE `motion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `motionSlug` (`consultationId`, `slug`),
  ADD KEY `consultation` (`consultationId`),
  ADD KEY `parent_motion` (`parentMotionId`),
  ADD KEY `type` (`motionTypeId`),
  ADD KEY `motion_reference_am` (`proposalReferenceId`),
  ADD KEY `agendaItemId` (`agendaItemId`),
  ADD KEY `ix_motion_voting_block` (`votingBlockId`),
  ADD KEY `fk_motion_responsibility` (`responsibilityId`);

--
-- Indexes for table `motionAdminComment`
--
ALTER TABLE `motionAdminComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `motionId` (`motionId`);

--
-- Indexes for table `motionComment`
--
ALTER TABLE `motionComment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_userIdx` (`userId`),
  ADD KEY `fk_comment_notion_idx` (`motionId`, `sectionId`),
  ADD KEY `fk_motion_comment_parents` (`parentCommentId`);

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
-- Indexes for table `texTemplate`
--
ALTER TABLE `texTemplate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siteId` (`siteId`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auth_UNIQUE` (`auth`);

--
-- Indexes for table `userNotification`
--
ALTER TABLE `userNotification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `notificationType`, `notificationReferenceId`);

--
-- Indexes for table `votingBlock`
--
ALTER TABLE `votingBlock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voting_block_consultation` (`consultationId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amendment`
--
ALTER TABLE `amendment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentAdminComment`
--
ALTER TABLE `amendmentAdminComment`
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
-- AUTO_INCREMENT for table `consultationFile`
--
ALTER TABLE `consultationFile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationLog`
--
ALTER TABLE `consultationLog`
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
-- AUTO_INCREMENT for table `motionAdminComment`
--
ALTER TABLE `motionAdminComment`
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
-- AUTO_INCREMENT for table `texTemplate`
--
ALTER TABLE `texTemplate`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `userNotification`
--
ALTER TABLE `userNotification`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `votingBlock`
--
ALTER TABLE `votingBlock`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `amendment`
--
ALTER TABLE `amendment`
  ADD CONSTRAINT `fk_amendment_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `amendment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `votingBlock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_responsibility` FOREIGN KEY (`responsibilityId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_amendment_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentAdminComment`
--
ALTER TABLE `amendmentAdminComment`
  ADD CONSTRAINT `amendmentAdminComment_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`),
  ADD CONSTRAINT `amendmentAdminComment_ibfk_2` FOREIGN KEY (`userId`) REFERENCES `user` (`id`);

--
-- Constraints for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
  ADD CONSTRAINT `amendmentComment_ibfk_1` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`),
  ADD CONSTRAINT `fk_amendment_comment_parents` FOREIGN KEY (`parentCommentId`) REFERENCES `amendmentComment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_comment_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

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
-- Constraints for table `consultationFile`
--
ALTER TABLE `consultationFile`
  ADD CONSTRAINT `fk_consultation_file_site` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_file_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);

--
-- Constraints for table `consultationLog`
--
ALTER TABLE `consultationLog`
  ADD CONSTRAINT `consultationLog_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `consultationLog_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);

--
-- Constraints for table `consultationMotionType`
--
ALTER TABLE `consultationMotionType`
  ADD CONSTRAINT `consultationMotionType_ibfk_1` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`),
  ADD CONSTRAINT `consultationMotionType_ibfk_2` FOREIGN KEY (`texTemplateId`) REFERENCES `texTemplate` (`id`);

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
-- Constraints for table `consultationText`
--
ALTER TABLE `consultationText`
  ADD CONSTRAINT `fk_consultation_text_site` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_texts_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationUserPrivilege`
--
ALTER TABLE `consultationUserPrivilege`
  ADD CONSTRAINT `consultationUserPrivilege_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `consultationUserPrivilege_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);

--
-- Constraints for table `emailLog`
--
ALTER TABLE `emailLog`
  ADD CONSTRAINT `emailLog_ibfk_1` FOREIGN KEY (`fromSiteId`) REFERENCES `site` (`id`),
  ADD CONSTRAINT `fk_mail_log_user` FOREIGN KEY (`toUserId`) REFERENCES `user` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;

--
-- Constraints for table `motion`
--
ALTER TABLE `motion`
  ADD CONSTRAINT `fk_motion_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_motion_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `motion` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_motion_responsibility` FOREIGN KEY (`responsibilityId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_motion_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `votingBlock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_site_parent` FOREIGN KEY (`parentMotionId`) REFERENCES `motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `motion_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `consultationMotionType` (`id`),
  ADD CONSTRAINT `motion_ibfk_2` FOREIGN KEY (`agendaItemId`) REFERENCES `consultationAgendaItem` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `motionAdminComment`
--
ALTER TABLE `motionAdminComment`
  ADD CONSTRAINT `motionAdminComment_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `motionAdminComment_ibfk_2` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`);

--
-- Constraints for table `motionComment`
--
ALTER TABLE `motionComment`
  ADD CONSTRAINT `fk_motion_comment_parents` FOREIGN KEY (`parentCommentId`) REFERENCES `motionComment` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
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
-- Constraints for table `texTemplate`
--
ALTER TABLE `texTemplate`
  ADD CONSTRAINT `texTemplate_ibfk_1` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`);

--
-- Constraints for table `userNotification`
--
ALTER TABLE `userNotification`
  ADD CONSTRAINT `userNotification_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `userNotification_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);

--
-- Constraints for table `votingBlock`
--
ALTER TABLE `votingBlock`
  ADD CONSTRAINT `fk_voting_block_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
