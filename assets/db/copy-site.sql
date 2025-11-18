-- This copies all entries from a multi-site installation that are associated with one particular site
-- and copies them to a separate database. Mind that the tables in the copy-database needs to have _exactly_ the same schema.
-- Hint: Set SITE_ID to your needs, and replace antragsgruen_copy and antragsgruen_live with the actual values.

SET @SITE_ID = 1234;


SET FOREIGN_KEY_CHECKS = 0;

SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'TRADITIONAL,ALLOW_INVALID_DATES';

-- === SYSTEM ===
INSERT INTO antragsgruen_copy.migration
SELECT * FROM antragsgruen_live.migration;

INSERT INTO antragsgruen_copy.backgroundJob
SELECT b.*
FROM antragsgruen_live.backgroundJob b
WHERE b.siteId = @SITE_ID
   OR b.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);

INSERT INTO antragsgruen_copy.emailLog
SELECT e.*
FROM antragsgruen_live.emailLog e
WHERE e.fromSiteId = @SITE_ID;


-- === SITE ===
INSERT INTO antragsgruen_copy.site
SELECT * FROM antragsgruen_live.site WHERE id = @SITE_ID;


-- === CONSULTATIONS ===
INSERT INTO antragsgruen_copy.consultation
SELECT c.*
FROM antragsgruen_live.consultation c
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationAgendaItem
SELECT ai.*
FROM antragsgruen_live.consultationAgendaItem ai
         JOIN antragsgruen_live.consultation c ON ai.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationFileGroup
SELECT fg.*
FROM antragsgruen_live.consultationFileGroup fg
         JOIN antragsgruen_live.consultation c ON fg.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationFile
SELECT f.*
FROM antragsgruen_live.consultationFile f
WHERE f.siteId = @SITE_ID
   OR f.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);

INSERT INTO antragsgruen_copy.consultationLog
SELECT l.*
FROM antragsgruen_live.consultationLog l
         JOIN antragsgruen_live.consultation c ON l.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationMotionType
SELECT mt.*
FROM antragsgruen_live.consultationMotionType mt
         JOIN antragsgruen_live.consultation c ON mt.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationSettingsMotionSection
SELECT ms.*
FROM antragsgruen_live.consultationSettingsMotionSection ms
         JOIN antragsgruen_live.consultationMotionType mt ON ms.motionTypeId = mt.id
         JOIN antragsgruen_live.consultation c ON mt.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationSettingsTag
SELECT t.*
FROM antragsgruen_live.consultationSettingsTag t
         JOIN antragsgruen_live.consultation c ON t.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.consultationText
SELECT txt.*
FROM antragsgruen_live.consultationText txt
WHERE txt.siteId = @SITE_ID
   OR txt.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);

INSERT INTO antragsgruen_copy.consultationUserGroup
SELECT g.*
FROM antragsgruen_live.consultationUserGroup g
WHERE g.siteId = @SITE_ID
   OR g.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);


-- === MOTIONS ===
INSERT INTO antragsgruen_copy.motion
SELECT m.*
FROM antragsgruen_live.motion m
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionAdminComment
SELECT mc.*
FROM antragsgruen_live.motionAdminComment mc
         JOIN antragsgruen_live.motion m ON mc.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionComment
SELECT mc.*
FROM antragsgruen_live.motionComment mc
         JOIN antragsgruen_live.motion m ON mc.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionCommentSupporter
SELECT mcs.*
FROM antragsgruen_live.motionCommentSupporter mcs
         JOIN antragsgruen_live.motionComment mc ON mcs.motionCommentId = mc.id
         JOIN antragsgruen_live.motion m ON mc.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionProposal
SELECT mp.*
FROM antragsgruen_live.motionProposal mp
         JOIN antragsgruen_live.motion m ON mp.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionSection
SELECT s.*
FROM antragsgruen_live.motionSection s
         JOIN antragsgruen_live.motion m ON s.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionSubscription
SELECT ms.*
FROM antragsgruen_live.motionSubscription ms
         JOIN antragsgruen_live.motion m ON ms.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionSupporter
SELECT ms.*
FROM antragsgruen_live.motionSupporter ms
         JOIN antragsgruen_live.motion m ON ms.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.motionTag
SELECT mt.*
FROM antragsgruen_live.motionTag mt
         JOIN antragsgruen_live.motion m ON mt.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;


-- === AMENDMENTS ===
INSERT INTO antragsgruen_copy.amendment
SELECT a.*
FROM antragsgruen_live.amendment a
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentAdminComment
SELECT s.*
FROM antragsgruen_live.amendmentAdminComment s
         JOIN antragsgruen_live.amendment a ON s.amendmentId = a.id
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentComment
SELECT s.*
FROM antragsgruen_live.amendmentComment s
         JOIN antragsgruen_live.amendment a ON s.amendmentId = a.id
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentProposal
SELECT s.*
FROM antragsgruen_live.amendmentProposal s
         JOIN antragsgruen_live.amendment a ON s.amendmentId = a.id
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentSection
SELECT s.*
FROM antragsgruen_live.amendmentSection s
         JOIN antragsgruen_live.amendment a ON s.amendmentId = a.id
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentSupporter
SELECT asp.*
FROM antragsgruen_live.amendmentSupporter asp
         JOIN antragsgruen_live.amendment a ON asp.amendmentId = a.id
         JOIN antragsgruen_live.motion m ON a.motionId = m.id
         JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.amendmentTag
SELECT at.*
FROM antragsgruen_live.amendmentTag at
JOIN antragsgruen_live.amendment a ON at.amendmentId = a.id
    JOIN antragsgruen_live.motion m ON a.motionId = m.id
    JOIN antragsgruen_live.consultation c ON m.consultationId = c.id
WHERE c.siteId = @SITE_ID;

-- === VOTING ===
INSERT INTO antragsgruen_copy.votingBlock
SELECT vb.*
FROM antragsgruen_live.votingBlock vb
         JOIN antragsgruen_live.consultation c ON vb.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.votingQuestion
SELECT vq.*
FROM antragsgruen_live.votingQuestion vq
         JOIN antragsgruen_live.consultation c ON vq.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.vote
SELECT v.*
FROM antragsgruen_live.vote v
         LEFT JOIN antragsgruen_live.motion m ON v.motionId = m.id
         LEFT JOIN antragsgruen_live.amendment a ON v.amendmentId = a.id
         LEFT JOIN antragsgruen_live.votingQuestion q ON v.questionId = q.id
         LEFT JOIN antragsgruen_live.consultation c
                   ON c.id = COALESCE(m.consultationId, q.consultationId)
WHERE c.siteId = @SITE_ID;


-- === SPEECH QUEUE ===
INSERT INTO antragsgruen_copy.speechQueue
SELECT sq.*
FROM antragsgruen_live.speechQueue sq
         JOIN antragsgruen_live.consultation c ON sq.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.speechSubqueue
SELECT ss.*
FROM antragsgruen_live.speechSubqueue ss
         JOIN antragsgruen_live.speechQueue sq ON ss.queueId = sq.id
         JOIN antragsgruen_live.consultation c ON sq.consultationId = c.id
WHERE c.siteId = @SITE_ID;

INSERT INTO antragsgruen_copy.speechQueueItem
SELECT si.*
FROM antragsgruen_live.speechQueueItem si
         JOIN antragsgruen_live.speechQueue sq ON si.queueId = sq.id
         JOIN antragsgruen_live.consultation c ON sq.consultationId = c.id
WHERE c.siteId = @SITE_ID;


-- === USERS ===

INSERT INTO antragsgruen_copy.userGroup
SELECT ug.*
FROM antragsgruen_live.userGroup ug
         JOIN antragsgruen_live.consultationUserGroup g ON ug.groupId = g.id
WHERE g.siteId = @SITE_ID
   OR g.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);

INSERT INTO antragsgruen_copy.userConsultationScreening
SELECT us.*
FROM antragsgruen_live.userConsultationScreening us
WHERE us.consultationId IN (SELECT id FROM antragsgruen_live.consultation WHERE siteId = @SITE_ID);

-- Create temporary table with all User IDs

DROP TEMPORARY TABLE IF EXISTS antragsgruen_copy.tmp_user_ids;
CREATE TEMPORARY TABLE antragsgruen_copy.tmp_user_ids (id INT PRIMARY KEY);

INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.consultationLog WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT uploadedById FROM antragsgruen_copy.consultationFile WHERE uploadedById IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT responsibilityId FROM antragsgruen_copy.motion WHERE responsibilityId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT responsibilityId FROM antragsgruen_copy.amendment WHERE responsibilityId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.motionSupporter WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.amendmentSupporter WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.motionComment WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.amendmentComment WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.motionAdminComment WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.amendmentAdminComment WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.vote WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT toUserId FROM antragsgruen_copy.emailLog WHERE toUserId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.speechQueueItem WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.userGroup WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.userNotification WHERE userId IS NOT NULL;
INSERT IGNORE INTO antragsgruen_copy.tmp_user_ids SELECT DISTINCT userId FROM antragsgruen_copy.userConsultationScreening WHERE userId IS NOT NULL;


INSERT INTO antragsgruen_copy.user
SELECT u.*
FROM antragsgruen_live.user u
         JOIN antragsgruen_copy.tmp_user_ids t ON t.id = u.id
WHERE u.id NOT IN (SELECT id FROM antragsgruen_copy.user);

-- === FINISH ===
SET FOREIGN_KEY_CHECKS = 1;




-- === CHECK CONSISTENCY  ===
-- https://stackoverflow.com/a/5977191

DELIMITER $$

DROP PROCEDURE IF EXISTS ANALYZE_INVALID_FOREIGN_KEYS$$

CREATE
    PROCEDURE `ANALYZE_INVALID_FOREIGN_KEYS`(
    checked_database_name VARCHAR(64),
    checked_table_name VARCHAR(64),
    temporary_result_table ENUM('Y', 'N'))

    LANGUAGE SQL
    NOT DETERMINISTIC
    READS SQL DATA

BEGIN
        DECLARE TABLE_SCHEMA_VAR VARCHAR(64);
        DECLARE TABLE_NAME_VAR VARCHAR(64);
        DECLARE COLUMN_NAME_VAR VARCHAR(64);
        DECLARE CONSTRAINT_NAME_VAR VARCHAR(64);
        DECLARE REFERENCED_TABLE_SCHEMA_VAR VARCHAR(64);
        DECLARE REFERENCED_TABLE_NAME_VAR VARCHAR(64);
        DECLARE REFERENCED_COLUMN_NAME_VAR VARCHAR(64);
        DECLARE KEYS_SQL_VAR VARCHAR(1024);

        DECLARE done INT DEFAULT 0;

        DECLARE foreign_key_cursor CURSOR FOR
SELECT
    `TABLE_SCHEMA`,
    `TABLE_NAME`,
    `COLUMN_NAME`,
    `CONSTRAINT_NAME`,
    `REFERENCED_TABLE_SCHEMA`,
    `REFERENCED_TABLE_NAME`,
    `REFERENCED_COLUMN_NAME`
FROM
    information_schema.KEY_COLUMN_USAGE
WHERE
    `CONSTRAINT_SCHEMA` LIKE checked_database_name AND
    `TABLE_NAME` LIKE checked_table_name AND
    `REFERENCED_TABLE_SCHEMA` IS NOT NULL;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        IF temporary_result_table = 'N' THEN
            DROP TEMPORARY TABLE IF EXISTS INVALID_FOREIGN_KEYS;
DROP TABLE IF EXISTS INVALID_FOREIGN_KEYS;

CREATE TABLE INVALID_FOREIGN_KEYS(
                                     `TABLE_SCHEMA` VARCHAR(64),
                                     `TABLE_NAME` VARCHAR(64),
                                     `COLUMN_NAME` VARCHAR(64),
                                     `CONSTRAINT_NAME` VARCHAR(64),
                                     `REFERENCED_TABLE_SCHEMA` VARCHAR(64),
                                     `REFERENCED_TABLE_NAME` VARCHAR(64),
                                     `REFERENCED_COLUMN_NAME` VARCHAR(64),
                                     `INVALID_KEY_COUNT` INT,
                                     `INVALID_KEY_SQL` VARCHAR(1024)
);
ELSEIF temporary_result_table = 'Y' THEN
            DROP TEMPORARY TABLE IF EXISTS INVALID_FOREIGN_KEYS;
DROP TABLE IF EXISTS INVALID_FOREIGN_KEYS;

CREATE TEMPORARY TABLE INVALID_FOREIGN_KEYS(
                `TABLE_SCHEMA` VARCHAR(64),
                `TABLE_NAME` VARCHAR(64),
                `COLUMN_NAME` VARCHAR(64),
                `CONSTRAINT_NAME` VARCHAR(64),
                `REFERENCED_TABLE_SCHEMA` VARCHAR(64),
                `REFERENCED_TABLE_NAME` VARCHAR(64),
                `REFERENCED_COLUMN_NAME` VARCHAR(64),
                `INVALID_KEY_COUNT` INT,
                `INVALID_KEY_SQL` VARCHAR(1024)
            );
END IF;


OPEN foreign_key_cursor;
foreign_key_cursor_loop: LOOP
            FETCH foreign_key_cursor INTO
            TABLE_SCHEMA_VAR,
            TABLE_NAME_VAR,
            COLUMN_NAME_VAR,
            CONSTRAINT_NAME_VAR,
            REFERENCED_TABLE_SCHEMA_VAR,
            REFERENCED_TABLE_NAME_VAR,
            REFERENCED_COLUMN_NAME_VAR;
            IF done THEN
                LEAVE foreign_key_cursor_loop;
END IF;


            SET @from_part = CONCAT('FROM ', '`', TABLE_SCHEMA_VAR, '`.`', TABLE_NAME_VAR, '`', ' AS REFERRING ',
                 'LEFT JOIN `', REFERENCED_TABLE_SCHEMA_VAR, '`.`', REFERENCED_TABLE_NAME_VAR, '`', ' AS REFERRED ',
                 'ON (REFERRING', '.`', COLUMN_NAME_VAR, '`', ' = ', 'REFERRED', '.`', REFERENCED_COLUMN_NAME_VAR, '`', ') ',
                 'WHERE REFERRING', '.`', COLUMN_NAME_VAR, '`', ' IS NOT NULL ',
                 'AND REFERRED', '.`', REFERENCED_COLUMN_NAME_VAR, '`', ' IS NULL');
            SET @full_query = CONCAT('SELECT COUNT(*) ', @from_part, ' INTO @invalid_key_count;');
PREPARE stmt FROM @full_query;

EXECUTE stmt;
IF @invalid_key_count > 0 THEN
                INSERT INTO
                    INVALID_FOREIGN_KEYS
                SET
                    `TABLE_SCHEMA` = TABLE_SCHEMA_VAR,
                    `TABLE_NAME` = TABLE_NAME_VAR,
                    `COLUMN_NAME` = COLUMN_NAME_VAR,
                    `CONSTRAINT_NAME` = CONSTRAINT_NAME_VAR,
                    `REFERENCED_TABLE_SCHEMA` = REFERENCED_TABLE_SCHEMA_VAR,
                    `REFERENCED_TABLE_NAME` = REFERENCED_TABLE_NAME_VAR,
                    `REFERENCED_COLUMN_NAME` = REFERENCED_COLUMN_NAME_VAR,
                    `INVALID_KEY_COUNT` = @invalid_key_count,
                    `INVALID_KEY_SQL` = CONCAT('SELECT ',
                        'REFERRING.', '`', COLUMN_NAME_VAR, '` ', 'AS "Invalid: ', COLUMN_NAME_VAR, '", ',
                        'REFERRING.* ',
                        @from_part, ';');
END IF;
DEALLOCATE PREPARE stmt;

END LOOP foreign_key_cursor_loop;
    END$$

DELIMITER ;

CALL ANALYZE_INVALID_FOREIGN_KEYS('antragsgruen_copy%', '%', 'Y');
DROP PROCEDURE IF EXISTS ANALYZE_INVALID_FOREIGN_KEYS;

SELECT * FROM INVALID_FOREIGN_KEYS;
