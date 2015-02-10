SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

--
-- Table structure for table `amendment`
--

CREATE TABLE `amendment` (
  `id` int(11) NOT NULL,
  `motionId` int(11) DEFAULT NULL,
  `titlePrefix` varchar(45) DEFAULT NULL,
  `changedTitle` text,
  `changedParagraphs` longtext NOT NULL,
  `changedExplanation` longtext NOT NULL,
  `changeMetatext` longtext NOT NULL,
  `changeText` longtext NOT NULL,
  `changeExplanation` longtext NOT NULL,
  `changeExplanationHtml` tinyint(4) NOT NULL DEFAULT '0',
  `cacheFirstLineChanged` mediumint(9) NOT NULL,
  `cacheFirstLineRel` text,
  `cacheFirstLineAbs` text,
  `dateCreation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateResolution` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `statusString` varchar(55) NOT NULL,
  `noteInternal` text,
  `textFixed` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentComment`
--

CREATE TABLE `amendmentComment` (
  `id` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `amendmentId` int(11) DEFAULT NULL,
  `paragraph` smallint(6) DEFAULT NULL,
  `text` mediumtext,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) DEFAULT NULL,
  `replyNotification` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `amendmentSupporter`
--

CREATE TABLE `amendmentSupporter` (
  `id` int(11) NOT NULL,
  `amendmentId` int(11) NOT NULL,
  `position` smallint(6) NOT NULL DEFAULT '0',
  `userId` int(11) NOT NULL,
  `role` enum('initiates','supports','likes','dislikes') NOT NULL,
  `comment` mediumtext,
  `personType` tinyint(4) DEFAULT NULL,
  `name` text,
  `organization` text,
  `resolutionDate` date DEFAULT NULL,
  `contactEmail` varchar(100) DEFAULT NULL,
  `contactPhone` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `id` char(32) NOT NULL,
  `dateCreation` timestamp NULL DEFAULT NULL,
  `data` longblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `consultation` (
  `id` int(11) NOT NULL,
  `siteId` int(11) NOT NULL,
  `urlPath` varchar(45) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `titleShort` varchar(45) NOT NULL,
  `eventDateFrom` date DEFAULT NULL,
  `eventDateUntil` date DEFAULT NULL,
  `deadlineMotions` timestamp NULL DEFAULT NULL,
  `deadlineAmendments` timestamp NULL DEFAULT NULL,
  `policyMotions` varchar(20) DEFAULT NULL,
  `policyAmendments` varchar(20) DEFAULT NULL,
  `policyComments` varchar(20) DEFAULT NULL,
  `policySupport` varchar(20) DEFAULT NULL,
  `adminEmail` varchar(150) DEFAULT NULL,
  `settings` blob
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationAdmin`
--

CREATE TABLE `consultationAdmin` (
  `consultationId` int(11) NOT NULL,
  `userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationOdtTemplate`
--

CREATE TABLE `consultationOdtTemplate` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsMotionSection`
--

CREATE TABLE `consultationSettingsMotionSection` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `position` smallint(6) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `fixedWidth` tinyint(4) NOT NULL,
  `maxLen` int(11) DEFAULT NULL,
  `lineNumbers` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSettingsTag`
--

CREATE TABLE `consultationSettingsTag` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) DEFAULT NULL,
  `position` smallint(6) DEFAULT NULL,
  `title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationSubscription`
--

CREATE TABLE `consultationSubscription` (
  `consultationId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `motions` tinyint(4) DEFAULT NULL,
  `amendments` tinyint(4) DEFAULT NULL,
  `comments` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `consultationText`
--

CREATE TABLE `consultationText` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) DEFAULT NULL,
  `textId` varchar(20) NOT NULL,
  `text` longtext,
  `editDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `emailLog`
--

CREATE TABLE `emailLog` (
  `id` int(11) NOT NULL,
  `toEmail` varchar(200) DEFAULT NULL,
  `toUserId` int(11) DEFAULT NULL,
  `type` smallint(6) DEFAULT NULL,
  `fromEmail` varchar(200) DEFAULT NULL,
  `dateSent` timestamp NULL DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `text` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motion`
--

CREATE TABLE `motion` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `parentMotionId` int(11) DEFAULT NULL,
  `title` text NOT NULL,
  `titlePrefix` varchar(50) NOT NULL,
  `dateCreation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateResolution` varchar(45) DEFAULT NULL,
  `text` longtext,
  `explanation` longtext,
  `explanationHtml` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL,
  `statusString` varchar(55) DEFAULT NULL,
  `noteInternal` text,
  `cacheLineNumber` mediumint(8) unsigned NOT NULL,
  `cacheParagraphNumber` mediumint(8) unsigned NOT NULL,
  `textFixed` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionComment`
--

CREATE TABLE `motionComment` (
  `id` int(11) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `motionId` int(11) DEFAULT NULL,
  `paragraph` smallint(6) DEFAULT NULL,
  `text` mediumtext NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) DEFAULT NULL,
  `replyNotification` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionCommentSupporter`
--

CREATE TABLE `motionCommentSupporter` (
  `id` int(11) NOT NULL,
  `ipHash` char(32) DEFAULT NULL,
  `cookieId` int(11) DEFAULT NULL,
  `motionCommentId` int(11) NOT NULL,
  `likes` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSection`
--

CREATE TABLE `motionSection` (
  `motionId` int(11) NOT NULL,
  `sectionId` int(11) NOT NULL,
  `data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSubscription`
--

CREATE TABLE `motionSubscription` (
  `motionId` int(11) NOT NULL,
  `userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionSupporter`
--

CREATE TABLE `motionSupporter` (
  `id` int(11) NOT NULL,
  `motionId` int(11) NOT NULL,
  `position` smallint(6) NOT NULL DEFAULT '0',
  `userId` int(11) NOT NULL,
  `role` enum('initiates','supports','likes','dislikes') NOT NULL,
  `comment` mediumtext,
  `personType` tinyint(4) DEFAULT NULL,
  `name` text,
  `organization` text,
  `resolutionDate` date DEFAULT NULL,
  `contactEmail` varchar(100) DEFAULT NULL,
  `contactPhone` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `motionTag`
--

CREATE TABLE `motionTag` (
  `motionId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE `site` (
  `id` int(11) NOT NULL,
  `subdomain` varchar(45) NOT NULL,
  `title` varchar(200) NOT NULL,
  `titleShort` varchar(100) DEFAULT NULL,
  `settings` tinyblob,
  `currentConsultationId` int(11) DEFAULT NULL,
  `public` tinyint(4) DEFAULT '1',
  `contact` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `siteAdmin`
--

CREATE TABLE `siteAdmin` (
  `siteId` int(11) NOT NULL,
  `userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `emailConfirmed` tinyint(4) DEFAULT '0',
  `auth` varchar(190) DEFAULT NULL,
  `dateCreation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL,
  `pwdEnc` varchar(100) DEFAULT NULL,
  `authKey` binary(100) DEFAULT NULL,
  `siteNamespaceId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amendment`
--
ALTER TABLE `amendment`
ADD PRIMARY KEY (`id`), ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
ADD PRIMARY KEY (`id`), ADD KEY `fk_amendment_comment_userIdx` (`userId`), ADD KEY `fk_amendment_comment_amendmentIdx` (`amendmentId`);

--
-- Indexes for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
ADD PRIMARY KEY (`id`), ADD KEY `fk_amendmentIdx` (`amendmentId`), ADD KEY `fk_supporter_idx` (`userId`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `yii_url_UNIQUE` (`urlPath`,`siteId`), ADD KEY `fk_consultation_siteIdx` (`siteId`);

--
-- Indexes for table `consultationAdmin`
--
ALTER TABLE `consultationAdmin`
ADD PRIMARY KEY (`consultationId`,`userId`), ADD KEY `fk_consultation_userIdx` (`userId`), ADD KEY `fk_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
ADD PRIMARY KEY (`id`), ADD KEY `fk_consultationIdx` (`consultationId`);

--
-- Indexes for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
ADD PRIMARY KEY (`id`), ADD KEY `consultationId` (`consultationId`);

--
-- Indexes for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consultationSubscription`
--
ALTER TABLE `consultationSubscription`
ADD PRIMARY KEY (`consultationId`,`userId`), ADD KEY `fk_consultationIdx` (`consultationId`), ADD KEY `fk_userIdx` (`userId`);

--
-- Indexes for table `consultationText`
--
ALTER TABLE `consultationText`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `consultation_text_unique` (`textId`,`consultationId`), ADD KEY `fk_texts_consultationIdx` (`consultationId`);

--
-- Indexes for table `emailLog`
--
ALTER TABLE `emailLog`
ADD PRIMARY KEY (`id`), ADD KEY `fk_mail_log_userIdx` (`toUserId`);

--
-- Indexes for table `motion`
--
ALTER TABLE `motion`
ADD PRIMARY KEY (`id`), ADD KEY `consultation` (`consultationId`), ADD KEY `parent_motion` (`parentMotionId`);

--
-- Indexes for table `motionComment`
--
ALTER TABLE `motionComment`
ADD PRIMARY KEY (`id`), ADD KEY `fk_comment_userIdx` (`userId`), ADD KEY `fk_comment_notion_idx` (`motionId`);

--
-- Indexes for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `ip_hash_motion` (`ipHash`,`motionCommentId`), ADD UNIQUE KEY `cookie_motion` (`cookieId`,`motionCommentId`), ADD KEY `fk_motion_comment_supporter_commentIdx` (`motionCommentId`);

--
-- Indexes for table `motionSection`
--
ALTER TABLE `motionSection`
ADD PRIMARY KEY (`motionId`,`sectionId`), ADD KEY `motion_section_fk_sectionIdx` (`sectionId`);

--
-- Indexes for table `motionSubscription`
--
ALTER TABLE `motionSubscription`
ADD PRIMARY KEY (`motionId`,`userId`), ADD KEY `fk_motionId` (`motionId`), ADD KEY `fk_userId` (`userId`);

--
-- Indexes for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
ADD PRIMARY KEY (`id`), ADD KEY `fk_supporter_idx` (`userId`), ADD KEY `fk_motionIdx` (`motionId`);

--
-- Indexes for table `motionTag`
--
ALTER TABLE `motionTag`
ADD PRIMARY KEY (`motionId`,`tagId`), ADD KEY `motion_tag_fk_tagIdx` (`tagId`);

--
-- Indexes for table `site`
--
ALTER TABLE `site`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `subdomain_UNIQUE` (`subdomain`), ADD KEY `fk_veranstaltungsreihe_veranstaltung1_idx` (`currentConsultationId`);

--
-- Indexes for table `siteAdmin`
--
ALTER TABLE `siteAdmin`
ADD PRIMARY KEY (`siteId`,`userId`), ADD KEY `site_admin_fk_userIdx` (`userId`), ADD KEY `site_admin_fk_siteIdx` (`siteId`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `auth_UNIQUE` (`auth`), ADD KEY `fk_user_namespaceIdx` (`siteNamespaceId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amendment`
--
ALTER TABLE `amendment`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `consultation`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `consultationText`
--
ALTER TABLE `consultationText`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `emailLog`
--
ALTER TABLE `emailLog`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motion`
--
ALTER TABLE `motion`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionComment`
--
ALTER TABLE `motionComment`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `site`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `amendment`
--
ALTER TABLE `amendment`
ADD CONSTRAINT `fk_ammendment_motion` FOREIGN KEY (`motionId`) REFERENCES `amendment` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentComment`
--
ALTER TABLE `amendmentComment`
ADD CONSTRAINT `fk_amendment_comment_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_amendment_comment_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `amendmentSupporter`
--
ALTER TABLE `amendmentSupporter`
ADD CONSTRAINT `fk_support_amendment` FOREIGN KEY (`amendmentId`) REFERENCES `amendment` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `consultation`
--
ALTER TABLE `consultation`
ADD CONSTRAINT `fk_veranstaltung_veranstaltungsreihe1` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationAdmin`
--
ALTER TABLE `consultationAdmin`
ADD CONSTRAINT `fk_consultation_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationOdtTemplate`
--
ALTER TABLE `consultationOdtTemplate`
ADD CONSTRAINT `fk_odt_templates` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsMotionSection`
--
ALTER TABLE `consultationSettingsMotionSection`
ADD CONSTRAINT `consultation_settings_motion_section_fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSettingsTag`
--
ALTER TABLE `consultationSettingsTag`
ADD CONSTRAINT `consultation_tag_fk_consultation` FOREIGN KEY (`id`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationSubscription`
--
ALTER TABLE `consultationSubscription`
ADD CONSTRAINT `fk_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `consultationText`
--
ALTER TABLE `consultationText`
ADD CONSTRAINT `fk_texts_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `emailLog`
--
ALTER TABLE `emailLog`
ADD CONSTRAINT `fk_mail_log_user` FOREIGN KEY (`toUserId`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `motion`
--
ALTER TABLE `motion`
ADD CONSTRAINT `fk_motion_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_site_parent` FOREIGN KEY (`parentMotionId`) REFERENCES `motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `motionComment`
--
ALTER TABLE `motionComment`
ADD CONSTRAINT `fk_motion_comment_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_motion_comment_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `motionCommentSupporter`
--
ALTER TABLE `motionCommentSupporter`
ADD CONSTRAINT `fk_motion_comment_supporter_comment` FOREIGN KEY (`motionCommentId`) REFERENCES `motionComment` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `motionSection`
--
ALTER TABLE `motionSection`
ADD CONSTRAINT `motion_section_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `motion_section_fk_section` FOREIGN KEY (`sectionId`) REFERENCES `consultationSettingsMotionSection` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `motionSubscription`
--
ALTER TABLE `motionSubscription`
ADD CONSTRAINT `fk_subscription_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_subscription_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `motionSupporter`
--
ALTER TABLE `motionSupporter`
ADD CONSTRAINT `fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_supporter` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `motionTag`
--
ALTER TABLE `motionTag`
ADD CONSTRAINT `motion_tag_fk_motion` FOREIGN KEY (`motionId`) REFERENCES `motion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `motion_tag_fk_tag` FOREIGN KEY (`tagId`) REFERENCES `consultationSettingsTag` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `site`
--
ALTER TABLE `site`
ADD CONSTRAINT `fk_site_consultation` FOREIGN KEY (`currentConsultationId`) REFERENCES `consultation` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `siteAdmin`
--
ALTER TABLE `siteAdmin`
ADD CONSTRAINT `site_admin_fk_site` FOREIGN KEY (`siteId`) REFERENCES `site` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `site_admin_fk_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
ADD CONSTRAINT `fk_user_namespace` FOREIGN KEY (`siteNamespaceId`) REFERENCES `site` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
