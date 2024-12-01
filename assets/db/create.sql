SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

--
-- Table structure for table `amendment`
--

CREATE TABLE `###TABLE_PREFIX###amendment` (
  `id`                    INT(11)     NOT NULL,
  `motionId`              INT(11)              DEFAULT NULL,
  `agendaItemId`          INT(11)              DEFAULT NULL,
  `amendingAmendmentId`   INT(11)              DEFAULT NULL,
  `titlePrefix`           VARCHAR(45)          DEFAULT NULL,
  `changeEditorial`       LONGTEXT    NOT NULL,
  `changeText`            LONGTEXT    NOT NULL,
  `changeExplanation`     LONGTEXT    NOT NULL,
  `changeExplanationHtml` TINYINT(4)  NOT NULL DEFAULT '0',
  `cache`                 LONGTEXT    NOT NULL,
  `dateCreation`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePublication`       TIMESTAMP   NULL     DEFAULT NULL,
  `dateResolution`        TIMESTAMP   NULL     DEFAULT NULL,
  `dateContentModification` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`                TINYINT(4)  NOT NULL,
  `statusString`          VARCHAR(55) NOT NULL,
  `notCommentable`        TINYINT(4)  NOT NULL DEFAULT '0',
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
  `responsibilityComment` TEXT                 DEFAULT NULL,
  `extraData`             TEXT                 DEFAULT NULL
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
  `public`      TINYINT(3) NOT NULL DEFAULT 1,
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
-- Table structure for table `amendmentTag`
--

CREATE TABLE `###TABLE_PREFIX###amendmentTag` (
    `amendmentId` int(11) NOT NULL,
    `tagId`       int(11) NOT NULL
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
  `time`           VARCHAR(20)  NULL DEFAULT NULL,
  `code`           VARCHAR(20)  NOT NULL,
  `title`          VARCHAR(250) NOT NULL,
  `motionTypeId`   INT(11)           DEFAULT NULL,
  `settings`       TEXT         NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationFile`
--

CREATE TABLE `###TABLE_PREFIX###consultationFile` (
    `id`               int(11)      NOT NULL,
    `consultationId`   int(11)               DEFAULT NULL,
    `siteId`           int(11)               DEFAULT NULL,
    `fileGroupId`      int(11)               DEFAULT NULL,
    `downloadPosition` mediumint(9)          DEFAULT NULL,
    `filename`         varchar(250) NOT NULL,
    `title`            text                  DEFAULT NULL,
    `filesize`         int(11)      NOT NULL,
    `mimetype`         varchar(250) NOT NULL,
    `width`            int(11)               DEFAULT NULL,
    `height`           int(11)               DEFAULT NULL,
    `data`             mediumblob   NOT NULL,
    `dataHash`         varchar(40)  NOT NULL,
    `uploadedById`     int(11)               DEFAULT NULL,
    `dateCreation`     timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationFileGroup`
--

CREATE TABLE `###TABLE_PREFIX###consultationFileGroup` (
    `id` int(11) NOT NULL,
    `consultationId` int(11) NOT NULL,
    `consultationTextId` int(11) DEFAULT NULL,
    `parentGroupId` int(11) DEFAULT NULL,
    `position` int(11) NOT NULL,
    `title` varchar(250) NOT NULL
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
  `actionTime`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data`              TEXT NULL DEFAULT NULL
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
  `amendmentsOnly`               tinyint(4)   NOT NULL DEFAULT '0',
  `pdfLayout`                    int(11)      NOT NULL DEFAULT '0',
  `texTemplateId`                int(11)               DEFAULT NULL,
  `deadlines`                    text,
  `policyMotions`                text         NOT NULL,
  `policyAmendments`             text         NOT NULL,
  `policyComments`               text         NOT NULL,
  `policySupportMotions`         text         NOT NULL,
  `policySupportAmendments`      text         NOT NULL,
  `initiatorsCanMergeAmendments` tinyint(4)   NOT NULL DEFAULT '0',
  `motionLikesDislikes`          int(11)      NOT NULL,
  `amendmentLikesDislikes`       int(11)      NOT NULL,
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
  `printTitle`    TINYINT(4)   NOT NULL DEFAULT '1',
  `settings`      TEXT DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsTag`
--

CREATE TABLE `###TABLE_PREFIX###consultationSettingsTag` (
  `id`             INT(11) NOT NULL,
  `consultationId` INT(11) DEFAULT NULL,
  `parentTagId`    INT(11) DEFAULT NULL,
  `type`           TINYINT(4) NOT NULL DEFAULT 0,
  `position`       SMALLINT(6) DEFAULT NULL,
  `title`          VARCHAR(100) NOT NULL,
  `settings`       TEXT NULL DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationText`
--

CREATE TABLE `###TABLE_PREFIX###consultationText` (
  `id`             int(11)      NOT NULL,
  `motionTypeId`   int(11)           DEFAULT NULL,
  `consultationId` int(11)           DEFAULT NULL,
  `siteId`         int(11)           DEFAULT NULL,
  `category`       varchar(20)  NOT NULL,
  `textId`         varchar(100) NOT NULL,
  `menuPosition`   int(11)           DEFAULT NULL,
  `policyRead`     text              DEFAULT NULL,
  `title`          text              DEFAULT NULL,
  `breadcrumb`     text              DEFAULT NULL,
  `text`           longtext,
  `editDate`       timestamp    NULL DEFAULT CURRENT_TIMESTAMP
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationUserGroup`
--

CREATE TABLE `###TABLE_PREFIX###consultationUserGroup` (
    `id`             int(11)      NOT NULL,
    `externalId`     varchar(150)          DEFAULT NULL,
    `templateId`     tinyint(4)            DEFAULT NULL,
    `title`          varchar(150) NOT NULL,
    `consultationId` int(11)               DEFAULT NULL,
    `siteId`         int(11)               DEFAULT NULL,
    `position`       int(11)      NOT NULL DEFAULT 0,
    `selectable`     tinyint(4)   NOT NULL DEFAULT 1,
    `permissions`    text                  DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `emailBlocklist`
--

CREATE TABLE `###TABLE_PREFIX###emailBlocklist` (
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
-- Table structure for table `###TABLE_PREFIX###failedLoginAttempt`
--

CREATE TABLE `###TABLE_PREFIX###failedLoginAttempt` (
  `id` int(11) NOT NULL,
  `ipHash` char(64) NOT NULL,
  `username` varchar(190) NOT NULL,
  `dateAttempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `version`               VARCHAR(50) NOT NULL DEFAULT '1',
  `dateCreation`          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datePublication`       TIMESTAMP   NULL     DEFAULT NULL,
  `dateResolution`        TIMESTAMP   NULL     DEFAULT NULL,
  `dateContentModification` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`                TINYINT(4)  NOT NULL,
  `statusString`          VARCHAR(55)          DEFAULT NULL,
  `nonAmendable`          TINYINT(4)  NOT NULL DEFAULT '0',
  `notCommentable`        TINYINT(4)  NOT NULL DEFAULT '0',
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
  `responsibilityComment` TEXT                 DEFAULT NULL,
  `extraData`             TEXT                 DEFAULT NULL
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
  `public`    TINYINT(3) NOT NULL DEFAULT 1,
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
-- Table structure for table `speechQueue`
--

CREATE TABLE `###TABLE_PREFIX###speechQueue` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `agendaItemId` int(11) DEFAULT NULL,
  `motionId` int(11) DEFAULT NULL,
  `isActive` tinyint(4) NOT NULL DEFAULT 0,
  `settings` TEXT NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `speechQueueItem`
--

CREATE TABLE `###TABLE_PREFIX###speechQueueItem` (
  `id` int(11) NOT NULL,
  `queueId` int(11) NOT NULL,
  `subqueueId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `userToken` VARCHAR(32) NULL DEFAULT NULL,
  `name` text NOT NULL,
  `position` int(11) NULL DEFAULT NULL,
  `dateApplied` timestamp NULL DEFAULT NULL,
  `dateStarted` timestamp NULL DEFAULT NULL,
  `dateStopped` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `speechSubqueue`
--

CREATE TABLE `###TABLE_PREFIX###speechSubqueue` (
  `id` int(11) NOT NULL,
  `queueId` int(11) NOT NULL,
  `name` text NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `dateLastLogin`   TIMESTAMP   NULL     DEFAULT NULL,
  `status`          TINYINT(4)  NOT NULL,
  `pwdEnc`          VARCHAR(100)         DEFAULT NULL,
  `authKey`         BINARY(100) NOT NULL,
  `secretKey`       VARCHAR(100)         DEFAULT NULL,
  `recoveryToken`   VARCHAR(100)         DEFAULT NULL,
  `recoveryAt`      TIMESTAMP   NULL     DEFAULT NULL,
  `emailChange`     VARCHAR(255)         DEFAULT NULL,
  `emailChangeAt`   TIMESTAMP   NULL     DEFAULT NULL,
  `settings`        TEXT        NULL     DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `userConsultationScreening`
--

CREATE TABLE `###TABLE_PREFIX###userConsultationScreening` (
  `userId` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `dateCreation` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `userGroup`
--

CREATE TABLE `###TABLE_PREFIX###userGroup`
(
    `userId`  int(11) NOT NULL,
    `groupId` int(11) NOT NULL
) ENGINE = InnoDB
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
-- Table structure for table `vote`
--

CREATE TABLE `###TABLE_PREFIX###vote` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `votingBlockId` int(11) DEFAULT NULL,
  `motionId` int(11) DEFAULT NULL,
  `amendmentId` int(11) DEFAULT NULL,
  `questionId` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT '1',
  `vote` tinyint(4) NOT NULL,
  `public` tinyint(4) NOT NULL,
  `dateVote` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `votingBlock`
--

CREATE TABLE `###TABLE_PREFIX###votingBlock` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL,
  `majorityType` tinyint(4) DEFAULT NULL,
  `quorumType` tinyint(4) DEFAULT NULL,
  `votesPublic` tinyint(4) DEFAULT NULL,
  `resultsPublic` tinyint(4) DEFAULT NULL,
  `assignedToMotionId` int(11) DEFAULT NULL,
  `usersPresentByOrga` text DEFAULT NULL,
  `answers` text DEFAULT NULL,
  `policyVote` text DEFAULT NULL,
  `votingStatus` tinyint(4) NOT NULL,
  `activityLog` text DEFAULT NULL,
  `settings` text DEFAULT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- Table structure for table `votingQuestion`
--

CREATE TABLE `###TABLE_PREFIX###votingQuestion` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `title` text NOT NULL,
  `votingStatus` tinyint(4) DEFAULT NULL,
  `votingBlockId` int(11) DEFAULT NULL,
  `votingData` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `backgroundJob`
--

CREATE TABLE `###TABLE_PREFIX###backgroundJob` (
  `id` bigint UNSIGNED NOT NULL,
  `siteId` int DEFAULT NULL,
  `consultationId` int DEFAULT NULL,
  `type` varchar(150) NOT NULL,
  `dateCreation` timestamp NOT NULL,
  `dateStarted` timestamp NULL DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  `dateFinished` timestamp NULL DEFAULT NULL,
  `payload` mediumtext NOT NULL,
  `error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_motionIdx` (`motionId`),
  ADD KEY `amendment_reference_am` (`proposalReferenceId`),
  ADD KEY `ix_amendment_voting_block` (`votingBlockId`),
  ADD KEY `fk_amendment_responsibility` (`responsibilityId`),
  ADD KEY `fk_amendment_agenda` (`agendaItemId`),
  ADD KEY `fk_amendment_amending` (`amendingAmendmentId`),
  ADD KEY `amendment_status_string` (`statusString`);

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
  ADD KEY `fk_amendment_comment_amendmentIdx` (`amendmentId`),
  ADD KEY `fk_amendment_comment_parents` (`parentCommentId`);

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
-- Indexes for table `amendmentTag`
--
ALTER TABLE `###TABLE_PREFIX###amendmentTag`
  ADD PRIMARY KEY (`amendmentId`,`tagId`),
  ADD KEY `amendment_tag_fk_tagIdx` (`tagId`);

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
-- Indexes for table `consultationFile`
--

ALTER TABLE `###TABLE_PREFIX###consultationFile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_file_consultation` (`consultationId`),
  ADD KEY `consultation_file_site` (`siteId`),
  ADD KEY `fk_file_group` (`fileGroupId`),
  ADD KEY `fk_file_uploaded_by` (`uploadedById`);

--
-- Indexes for table `consultationFileGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationFileGroup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_filegroup_consultation` (`consultationId`),
  ADD KEY `fk_filegroup_parent` (`parentGroupId`),
  ADD KEY `file_groups_fk_texts` (`consultationTextId`);

--
-- Indexes for table `consultationLog`
--
ALTER TABLE `###TABLE_PREFIX###consultationLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `actionTime`) USING BTREE,
  ADD KEY `actionReferenceId` (`actionReferenceId`,`actionTime`);

--
-- Indexes for table `consultationMotionType`
--
ALTER TABLE `###TABLE_PREFIX###consultationMotionType`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultationId` (`consultationId`, `position`) USING BTREE,
  ADD KEY `texLayout` (`texTemplateId`);

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
  ADD KEY `parentTagId` (`parentTagId`),
  ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultation_text_unique` (`category`, `textId`, `consultationId`),
  ADD KEY `fk_texts_consultationIdx` (`consultationId`),
  ADD KEY `consultation_text_site` (`siteId`),
  ADD KEY `fk_text_motion_type` (`motionTypeId`);

--
-- Indexes for table `consultationUserGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationUserGroup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ix_usergroup_external_id` (`externalId`),
  ADD KEY `usergroup_fk_consultation` (`consultationId`),
  ADD KEY `usergroup_fk_site` (`siteId`);


--
-- Indexes for table `emailBlocklist`
--
ALTER TABLE `###TABLE_PREFIX###emailBlocklist`
  ADD PRIMARY KEY (`emailHash`);

--
-- Indexes for table `emailLog`
--
ALTER TABLE `###TABLE_PREFIX###emailLog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mail_log_userIdx` (`toUserId`),
  ADD KEY `fromSiteId` (`fromSiteId`);

--
-- Indexes for table `failedLoginAttempt`
--
ALTER TABLE `###TABLE_PREFIX###failedLoginAttempt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `failedlogin_ip` (`ipHash`),
  ADD KEY `failedlogin_username` (`username`);

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
  ADD KEY `motion_reference_am` (`proposalReferenceId`),
  ADD KEY `agendaItemId` (`agendaItemId`),
  ADD KEY `ix_motion_voting_block` (`votingBlockId`),
  ADD KEY `fk_motion_responsibility` (`responsibilityId`),
  ADD KEY `motion_status_string` (`statusString`);

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
  ADD KEY `fk_comment_notion_idx` (`motionId`, `sectionId`),
  ADD KEY `fk_motion_comment_parents` (`parentCommentId`);

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
-- Indexes for table `speechQueue`
--
ALTER TABLE `###TABLE_PREFIX###speechQueue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_speech_consultation` (`consultationId`),
  ADD KEY `fk_speech_motion` (`motionId`),
  ADD KEY `fk_speech_agenda` (`agendaItemId`);

--
-- Indexes for table `speechSubqueue`
--
ALTER TABLE `###TABLE_PREFIX###speechSubqueue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_speech_queue` (`queueId`);

--
-- Indexes for table `speechQueueItem`
--
ALTER TABLE `###TABLE_PREFIX###speechQueueItem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_speechitem_queue` (`queueId`),
  ADD KEY `fk_speechitem_subqueue` (`subqueueId`),
  ADD KEY `fk_speechitem_user` (`userId`);

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
-- Indexes for table `userConsultationScreening`
--
ALTER TABLE `###TABLE_PREFIX###userConsultationScreening`
  ADD PRIMARY KEY (`userId`,`consultationId`),
  ADD KEY `userscreen_con_ix` (`consultationId`);

--
-- Indexes for table `userGroup`
--
ALTER TABLE `###TABLE_PREFIX###userGroup`
  ADD PRIMARY KEY (`userId`,`groupId`),
  ADD KEY `usergroup_group_ix` (`groupId`);

--
-- Indexes for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userId` (`userId`),
  ADD KEY `consultationId` (`consultationId`, `notificationType`, `notificationReferenceId`);

--
-- Indexes for table `vote`
--
ALTER TABLE `###TABLE_PREFIX###vote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vote_user` (`userId`),
  ADD KEY `fk_vote_vote` (`votingBlockId`),
  ADD KEY `fk_vote_motion` (`motionId`),
  ADD KEY `fk_vote_amendment` (`amendmentId`),
  ADD KEY `fk_vote_question` (`questionId`);

--
-- Indexes for table `votingBlock`
--
ALTER TABLE `###TABLE_PREFIX###votingBlock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voting_block_consultation` (`consultationId`),
  ADD KEY `fk_votingblock_assigned_to_motion` (`assignedToMotionId`);

--
-- Indexes for table `votingQuestion`
--
ALTER TABLE `###TABLE_PREFIX###votingQuestion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_question_block` (`votingBlockId`),
  ADD KEY `fk_question_consultation` (`consultationId`);

--
-- Indexes for table `backgroundJob`
--
ALTER TABLE `###TABLE_PREFIX###backgroundJob`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_background_site` (`siteId`),
  ADD KEY `fk_background_consultation` (`consultationId`),
  ADD KEY `ix_background_pending` (`dateStarted`,`id`),
  ADD KEY `ix_background_todelete` (`dateFinished`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
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
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `###TABLE_PREFIX###consultation`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationAgendaItem`
--
ALTER TABLE `###TABLE_PREFIX###consultationAgendaItem`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationFile`
--
ALTER TABLE `###TABLE_PREFIX###consultationFile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationFileGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationFileGroup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationLog`
--
ALTER TABLE `###TABLE_PREFIX###consultationLog`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationMotionType`
--
ALTER TABLE `###TABLE_PREFIX###consultationMotionType`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsMotionSection`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsMotionSection`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsTag`
--
ALTER TABLE `###TABLE_PREFIX###consultationSettingsTag`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationUserGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationUserGroup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `emailLog`
--
ALTER TABLE `###TABLE_PREFIX###emailLog`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `failedLoginAttempt`
--
ALTER TABLE `###TABLE_PREFIX###failedLoginAttempt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motion`
--
ALTER TABLE `###TABLE_PREFIX###motion`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
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
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `###TABLE_PREFIX###site`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `speechQueue`
--
ALTER TABLE `###TABLE_PREFIX###speechQueue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `speechSubqueue`
--
ALTER TABLE `###TABLE_PREFIX###speechSubqueue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `speechQueueItem`
--
ALTER TABLE `###TABLE_PREFIX###speechQueueItem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `texTemplate`
--
ALTER TABLE `###TABLE_PREFIX###texTemplate`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `###TABLE_PREFIX###user`
    MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for table `userGroup`
--
ALTER TABLE `###TABLE_PREFIX###userGroup`
  ADD CONSTRAINT `usergroup_fk_group` FOREIGN KEY (`groupId`) REFERENCES `###TABLE_PREFIX###consultationUserGroup` (`id`),
  ADD CONSTRAINT `usergroup_fk_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`);
--
-- AUTO_INCREMENT for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `vote`
--
ALTER TABLE `###TABLE_PREFIX###vote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `votingBlock`
--
ALTER TABLE `###TABLE_PREFIX###votingBlock`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `votingQuestion`
--
ALTER TABLE `###TABLE_PREFIX###votingQuestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backgroundJob`
--
ALTER TABLE `###TABLE_PREFIX###backgroundJob`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `amendment`
--
ALTER TABLE `###TABLE_PREFIX###amendment`
  ADD CONSTRAINT `fk_amendment_agenda` FOREIGN KEY (`agendaItemId`) REFERENCES `###TABLE_PREFIX###consultationAgendaItem` (`id`),
  ADD CONSTRAINT `fk_amendment_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `###TABLE_PREFIX###votingBlock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_responsibility` FOREIGN KEY (`responsibilityId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `fk_amendment_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_amendment_amending` FOREIGN KEY (`amendingAmendmentId`) REFERENCES `amendment` (`id`);

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
  ADD CONSTRAINT `fk_amendment_comment_parents` FOREIGN KEY (`parentCommentId`) REFERENCES `###TABLE_PREFIX###amendmentComment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amendment_comment_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

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
-- Constraints for table `amendmentTag`
--
ALTER TABLE `###TABLE_PREFIX###amendmentTag`
  ADD CONSTRAINT `amendment_tag_fk_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`),
  ADD CONSTRAINT `amendment_tag_fk_tag` FOREIGN KEY (`tagId`) REFERENCES `###TABLE_PREFIX###consultationSettingsTag` (`id`);

--
-- Constraints for table `consultation`
--
ALTER TABLE `###TABLE_PREFIX###consultation`
  ADD CONSTRAINT `fk_consultation_site1` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`)
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
-- Constraints for table `consultationFile`
--
ALTER TABLE `###TABLE_PREFIX###consultationFile`
  ADD CONSTRAINT `fk_consultation_file_site` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_file_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
  ADD CONSTRAINT `fk_file_group` FOREIGN KEY (`fileGroupId`) REFERENCES `###TABLE_PREFIX###consultationFileGroup` (`id`),
  ADD CONSTRAINT `fk_file_uploaded_by` FOREIGN KEY (`uploadedById`) REFERENCES `###TABLE_PREFIX###user` (`id`);

--
-- Constraints for table `consultationFileGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationFileGroup`
    ADD CONSTRAINT `file_groups_fk_texts` FOREIGN KEY (`consultationTextId`) REFERENCES `###TABLE_PREFIX###consultationText` (`id`),
    ADD CONSTRAINT `fk_filegroup_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
    ADD CONSTRAINT `fk_filegroup_parent` FOREIGN KEY (`parentGroupId`) REFERENCES `###TABLE_PREFIX###consultationFileGroup` (`id`);

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
  ON UPDATE NO ACTION,
  ADD CONSTRAINT `tag_fk_tags` FOREIGN KEY (`parentTagId`) REFERENCES `###TABLE_PREFIX###consultationSettingsTag` (`id`)
  ON DELETE SET NULL
  ON UPDATE NO ACTION;

--
-- Constraints for table `consultationText`
--
ALTER TABLE `###TABLE_PREFIX###consultationText`
  ADD CONSTRAINT `fk_consultation_text_site` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_text_motion_type` FOREIGN KEY (`motionTypeId`) REFERENCES `###TABLE_PREFIX###consultationMotionType` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_texts_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationUserGroup`
--
ALTER TABLE `###TABLE_PREFIX###consultationUserGroup`
  ADD CONSTRAINT `usergroup_fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
  ADD CONSTRAINT `usergroup_fk_site` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`);

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
  ADD CONSTRAINT `fk_motion_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_motion_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_motion_responsibility` FOREIGN KEY (`responsibilityId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `fk_motion_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `###TABLE_PREFIX###votingBlock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_site_parent` FOREIGN KEY (`parentMotionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `motion_ibfk_1` FOREIGN KEY (`motionTypeId`) REFERENCES `###TABLE_PREFIX###consultationMotionType` (`id`),
  ADD CONSTRAINT `motion_ibfk_2` FOREIGN KEY (`agendaItemId`) REFERENCES `###TABLE_PREFIX###consultationAgendaItem` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

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
  ADD CONSTRAINT `fk_motion_comment_parents` FOREIGN KEY (`parentCommentId`) REFERENCES `###TABLE_PREFIX###motionComment` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
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
-- Constraints for table `speechQueue`
--
ALTER TABLE `###TABLE_PREFIX###speechQueue`
  ADD CONSTRAINT `fk_speech_agenda` FOREIGN KEY (`agendaItemId`) REFERENCES `###TABLE_PREFIX###consultationAgendaItem` (`id`),
  ADD CONSTRAINT `fk_speech_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`),
  ADD CONSTRAINT `fk_speech_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);

--
-- Constraints for table `speechSubqueue`
--
ALTER TABLE `###TABLE_PREFIX###speechSubqueue`
  ADD CONSTRAINT `fk_speech_queue` FOREIGN KEY (`queueId`) REFERENCES `###TABLE_PREFIX###speechQueue` (`id`);

--
-- Constraints for table `speechQueueItem`
--
ALTER TABLE `###TABLE_PREFIX###speechQueueItem`
  ADD CONSTRAINT `fk_speechitem_queue` FOREIGN KEY (`queueId`) REFERENCES `###TABLE_PREFIX###speechQueue` (`id`),
  ADD CONSTRAINT `fk_speechitem_subqueue` FOREIGN KEY (`subqueueId`) REFERENCES `###TABLE_PREFIX###speechSubqueue` (`id`),
  ADD CONSTRAINT `fk_speechitem_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`);

--
-- Constraints for table `texTemplate`
--
ALTER TABLE `###TABLE_PREFIX###texTemplate`
  ADD CONSTRAINT `texTemplate_ibfk_1` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`);

--
-- Constraints for table `userConsultationScreening`
--
ALTER TABLE `###TABLE_PREFIX###userConsultationScreening`
  ADD CONSTRAINT `userscreen_fk_con` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
  ADD CONSTRAINT `userscreen_fk_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`);

--
-- Constraints for table `userNotification`
--
ALTER TABLE `###TABLE_PREFIX###userNotification`
  ADD CONSTRAINT `userNotification_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `userNotification_ibfk_2` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);

--
-- Constraints for table `vote`
--
ALTER TABLE `###TABLE_PREFIX###vote`
  ADD CONSTRAINT `fk_vote_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `###TABLE_PREFIX###amendment` (`id`),
  ADD CONSTRAINT `fk_vote_motion` FOREIGN KEY (`motionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`),
  ADD CONSTRAINT `fk_vote_question` FOREIGN KEY (`questionId`) REFERENCES `###TABLE_PREFIX###votingQuestion` (`id`),
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`userId`) REFERENCES `###TABLE_PREFIX###user` (`id`),
  ADD CONSTRAINT `fk_vote_vote` FOREIGN KEY (`votingBlockId`) REFERENCES `###TABLE_PREFIX###votingBlock` (`id`);

--
-- Constraints for table `votingBlock`
--
ALTER TABLE `###TABLE_PREFIX###votingBlock`
  ADD CONSTRAINT `fk_voting_block_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
  ADD CONSTRAINT `fk_votingblock_assigned_to_motion` FOREIGN KEY (`assignedToMotionId`) REFERENCES `###TABLE_PREFIX###motion` (`id`);

--
-- Constraints for table `votingQuestion`
--
ALTER TABLE `###TABLE_PREFIX###votingQuestion`
  ADD CONSTRAINT `fk_question_block` FOREIGN KEY (`votingBlockId`) REFERENCES `###TABLE_PREFIX###votingBlock` (`id`),
  ADD CONSTRAINT `fk_question_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`);

--
-- Constraints for table `backgroundJob`
--
ALTER TABLE `###TABLE_PREFIX###backgroundJob`
    ADD CONSTRAINT `fk_background_consultation` FOREIGN KEY (`consultationId`) REFERENCES `###TABLE_PREFIX###consultation` (`id`),
    ADD CONSTRAINT `fk_background_site` FOREIGN KEY (`siteId`) REFERENCES `###TABLE_PREFIX###site` (`id`);

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
