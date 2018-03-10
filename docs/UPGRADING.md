## Updating a existing installation using the pre-bundled package

- Download the latest package of Antragsgrün
- Extract the files to your web folder, overwriting all existing files. The configuration (in config/config.json) will not be affected by this.
- Remove the ``config/INSTALLING`` file

### If you have shell access to the server

- Execute ``./yii migrate`` on the command line to apply database changes

### If you don't have shell access to the server, e.g. using (S)FTP

If you don't have shell access to the server, installing major new versions (e.g. upgrading from 3.6 to 3.7) can be a tricky, as you will have to apply the database changes manually using phpMyAdmin or any other means of executing SQL commands.

Here is a list of SQL statements necessary to upgrade, starting with the upgrade from 3.6 to 3.7:

#### Upgrading from 3.7 to 3.8

```sql
ALTER TABLE `amendment` ADD `proposalStatus` TINYINT NULL DEFAULT NULL,
                        ADD `proposalReferenceId` INT NOT NULL,
                        ADD `proposalComment` TEXT NOT NULL,
                        ADD `votingStatus` TINYINT NULL DEFAULT NULL,
                        ADD `votingBlockId` INT NULL DEFAULT NULL,
                        ADD `proposalVisibleFrom` TIMESTAMP NULL DEFAULT NULL,
                        ADD `proposalNotification` TIMESTAMP NULL DEFAULT NULL,
                        ADD `proposalUserStatus` TINYINT NULL DEFAULT NULL,
                        ADD `proposalExplanation` TEXT NULL DEFAULT NULL,
                        ADD INDEX(`proposalReferenceId`),
                        ADD INDEX `ix_amendment_voting_block` (`votingBlockId`);

ALTER TABLE `motion` ADD `votingStatus` TINYINT NULL DEFAULT NULL AFTER `slug`,
                     ADD `votingBlockId` INT NULL DEFAULT NULL,
                     ADD `proposalStatus` TINYINT NULL DEFAULT NULL,
                     ADD `proposalReferenceId` INT NOT NULL,
                     ADD `proposalComment` TEXT NOT NULL,
                     ADD `proposalVisibleFrom` TIMESTAMP NULL DEFAULT NULL,
                     ADD `proposalNotification` TIMESTAMP NULL DEFAULT NULL,
                     ADD `proposalUserStatus` TINYINT NULL DEFAULT NULL,
                     ADD `proposalExplanation` TEXT NULL DEFAULT NULL,
                     ADD INDEX `motion_reference_am` (`proposalReferenceId`),
                     ADD INDEX `ix_motion_voting_block` (`votingBlockId`);

ALTER TABLE `consultationUserPrivilege` ADD `adminProposals` TINYINT NOT NULL DEFAULT '0' AFTER `adminScreen`;

CREATE TABLE `votingBlock` (
  `id` INT NOT NULL,
  `consultationId` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `votingStatus` TINYINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `votingBlock` (
  `id` int(11) NOT NULL,
  `consultationId` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `votingStatus` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `votingBlock` ADD PRIMARY KEY (`id`);
ALTER TABLE `votingBlock` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `user` ADD `organizationIds` TEXT NOT NULL;

ALTER TABLE `votingBlock` ADD CONSTRAINT `fk_voting_block_consultation` FOREIGN KEY (`consultationId`) REFERENCES `consultation`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `motion` ADD CONSTRAINT `fk_motion_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `votingBlock`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `motion` ADD CONSTRAINT `fk_motion_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `motion`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `amendment` ADD CONSTRAINT `fk_amendment_voting_block` FOREIGN KEY (`votingBlockId`) REFERENCES `votingBlock`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `amendment` ADD CONSTRAINT `fk_amendment_reference_am` FOREIGN KEY (`proposalReferenceId`) REFERENCES `amendment`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
```

#### Upgrading from 3.6 to 3.7

```sql
ALTER TABLE `consultationMotionType` ADD `initiatorsCanMergeAmendments` TINYINT NOT NULL DEFAULT '0' AFTER `policySupportAmendments`;
DROP TABLE `consultationAdmin`;
ALTER TABLE `amendment` ADD `globalAlternative` TINYINT NOT NULL DEFAULT '0';
INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m170226_134156_motionInitiatorsAmendmentMerging', 1489921851), ('m170419_182728_delete_consultation_admin', 1492626507), ('m170611_195343_global_alternatives', 1497211108);
```