SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';


INSERT INTO `consultation` (`id`, `siteId`, `urlPath`, `type`, `wording`, `title`, `titleShort`, `eventDateFrom`, `eventDateTo`, `deadlineMotions`, `deadlineAmendments`, `policyMotions`, `policyAmendments`, `policyComments`, `policySupport`, `amendmentNumbering`, `adminEmail`, `settings`) VALUES
  (1, 1, 'std-parteitag', 0, 0, 'Test2', '', NULL, NULL, NULL, NULL, 'all', 'all', 'all', 'all', 0, 'tobias@hoessl.eu', NULL);

INSERT INTO `consultationSettingsMotionSection` (`id`, `consultationId`, `motionTypeId`, `type`, `position`, `title`, `fixedWidth`, `maxLen`, `lineNumbers`, `hasComments`) VALUES
  (1, 1, NULL, 1, 1, 'Antragstext', 1, 0, 1, 0),
  (2, 1, NULL, 1, 2, 'Begründung', 0, 0, 0, 0);

INSERT INTO `consultationSettingsMotionType` (`id`, `consultationId`, `title`, `motionPrefix`, `position`) VALUES
  (1, 1, 'Antrag', 'A', 0),
  (2, 1, 'Resolution', 'R', 1),
  (3, 1, 'Satzungsantrag', 'S', 2);

INSERT INTO `site` (`id`, `subdomain`, `title`, `titleShort`, `settings`, `currentConsultationId`, `public`, `contact`) VALUES
  (1, 'stdparteitag', 'Test2', 'Test2', NULL, 1, 1, 'Test2');

INSERT INTO `siteAdmin` (`siteId`, `userId`) VALUES
  (1, 1);

INSERT INTO `user` (`id`, `name`, `email`, `emailConfirmed`, `auth`, `dateCreation`, `status`, `pwdEnc`, `authKey`, `siteNamespaceId`) VALUES
  (1, 'Testadmin', 'testadmin@example.org', 1, 'email:testadmin@example.org', '2015-03-21 11:04:44', 0, 'sha256:1000:gpdjLHGKeqKXDjjjVI6JsXF5xl+cAYm1:jT6RRYV6luIdDaomW56BMf50zQi0tiFy', NULL, NULL),
  (2, 'Testuser', 'testuser@example.org', 1, 'email:testuser@example.org', '2015-03-21 11:08:14', 0, 'sha256:1000:BwEqXMsdBXDi71XpQud1yRene4zeNRTt:atF5X6vaHJ93nyDIU/gobIpehez+0KBV', NULL, NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
