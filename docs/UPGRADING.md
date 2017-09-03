## Updating a existing installation using the pre-bundled package

- Download the latest package of Antragsgrün
- Extract the files to your web folder, overwriting all existing files. The configuration (in config/config.json) will not be affected by this.
- Remove the ``config/INSTALLING`` file

### If you have shell access to the server

- Execute ``./yii migrate`` on the command line to apply database changes

### If you don't have shell access to the server, e.g. using (S)FTP

If you don't have shell access to the server, installing major new versions (e.g. upgrading from 3.6 to 3.7) can be a tricky, as you will have to apply the database changes manually using phpMyAdmin or any other means of executing SQL commands.

Here is a list of SQL statements necessary to upgrade, starting with the upgrade from 3.6 to 3.7:

#### Upgrading from 3.6 to 3.7

```sql
ALTER TABLE `consultationMotionType` ADD `initiatorsCanMergeAmendments` TINYINT NOT NULL DEFAULT '0' AFTER `policySupportAmendments`;
DROP TABLE `consultationAdmin`;
ALTER TABLE `amendment` ADD `globalAlternative` TINYINT NOT NULL DEFAULT '0';
INSERT INTO `migration` (`version`, `apply_time`) VALUES ('m170226_134156_motionInitiatorsAmendmentMerging', 1489921851), ('m170419_182728_delete_consultation_admin', 1492626507), ('m170611_195343_global_alternatives', 1497211108);
```