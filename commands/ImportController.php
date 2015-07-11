<?php
namespace app\commands;

use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use app\models\db\User;
use yii\console\Controller;
use yii\db\Connection;

/**
 * Functions to import data (e.g. from old versions of Antragsgrün)
 * @package app\commands
 */
class ImportController extends Controller
{
    public static $MOTION_STATUS_MAP = [
        -2 => IMotion::STATUS_DELETED,
        -1 => IMotion::STATUS_WITHDRAWN,
        0  => IMotion::STATUS_DRAFT,
        1  => IMotion::STATUS_DRAFT,
        2  => IMotion::STATUS_SUBMITTED_UNSCREENED,
        3  => IMotion::STATUS_SUBMITTED_SCREENED,
        4  => IMotion::STATUS_ACCEPTED,
        5  => IMotion::STATUS_DECLINED,
        6  => IMotion::STATUS_MODIFIED_ACCEPTED,
        7  => IMotion::STATUS_MODIFIED,
        8  => IMotion::STATUS_ADOPTED,
        9  => IMotion::STATUS_COMPLETED,
        10 => IMotion::STATUS_REFERRED,
        11 => IMotion::STATUS_VOTE,
        12 => IMotion::STATUS_PAUSED,
        13 => IMotion::STATUS_MISSING_INFORMATION,
        14 => IMotion::STATUS_DISMISSED,
        15 => IMotion::STATUS_SUBMITTED_SCREENED,
    ];

    public static $PERSON_ROLE_MAP = [
        'initiator'    => 'initiates',
        'unterstuetzt' => 'supports',
        'mag'          => 'likes',
        'magnicht'     => 'dislikes',
    ];

    /**
     * @param string $name
     * @return string
     */
    private function istOrganisation($name)
    {
        if (mb_stripos($name, 'vorstand')) {
            return true;
        }
        if (mb_stripos($name, 'grüne jugend')) {
            return true;
        }
        return false;
    }

    /**
     * @param Connection $dbOld
     * @param array $siteRow
     * @param int $newSite
     */
    private function migrateSite(Connection $dbOld, $siteRow, $newSite)
    {
        echo 'Migrating: ' . $siteRow['subdomain'] . "\n";

    }

    /**
     * @param Connection $dbOld
     * @param int $veranstaltungAlt
     * @param int $consultationNew
     */
    private function migrateConsultation(Connection $dbOld, $veranstaltungAlt, $consultationNew)
    {
        /** @var Consultation $consultation */
        $consultation = Consultation::findOne($consultationNew);

        $command = $dbOld->createCommand('SELECT * FROM tags WHERE veranstaltung_id = ' . IntVal($veranstaltungAlt));
        $tags    = $command->queryAll();
        $tagMap  = [];
        foreach ($tags as $tag) {
            $newTag                 = new ConsultationSettingsTag();
            $newTag->consultationId = $consultation->id;
            $newTag->title          = $tag['name'];
            $newTag->position       = $tag['position'];
            if (!$newTag->save()) {
                var_dump($newTag->getErrors());
            }
            $tagMap[$tag['id']] = $newTag;
        }

        $command  = $dbOld->createCommand('SELECT * FROM antrag WHERE veranstaltung_id = ' . IntVal($veranstaltungAlt));
        $antraege = $command->queryAll();
        foreach ($antraege as $antrag) {
            echo 'Migrating Motion: ' . $antrag['id'] . "\n";
            $this->migrateMotion($dbOld, $antrag, $consultation, $tagMap);
        }
    }

    /**
     * @param Connection $dbOld
     * @param array $antrag
     * @param Consultation $consultation
     * @param ConsultationSettingsTag[] $tagMap
     */
    private function migrateMotion(Connection $dbOld, $antrag, Consultation $consultation, $tagMap)
    {
        $dateCreation           = ($antrag['datum_einreichung'] > 0 ? $antrag['datum_einreichung'] : null);
        $dateResolution         = ($antrag['datum_beschluss'] > 0 ? $antrag['datum_beschluss'] : null);
        $motion                 = new Motion();
        $motion->consultationId = $consultation->id;
        $motion->motionTypeId   = $consultation->motionTypes[0]->id;
        $motion->status         = static::$MOTION_STATUS_MAP[$antrag['status']];
        $motion->title          = $antrag['name'];
        $motion->titlePrefix    = $antrag['revision_name'];
        $motion->dateCreation   = $dateCreation;
        $motion->dateResolution = $dateResolution;
        $motion->statusString   = $antrag['status_string'];
        $motion->textFixed      = $antrag['text_unveraenderlich'];
        $motion->cache          = '';
        if (!$motion->save()) {
            var_dump($motion->getErrors());
        }

        $tags = $dbOld->createCommand('SELECT * FROM antrag_tags WHERE antrag_id = ' . $antrag['id'])->queryAll();
        foreach ($tags as $tag) {
            $newTag = $tagMap[$tag['tag_id']];
            $motion->link('tags', $newTag);
        }

        $sql                = 'SELECT * FROM antrag_unterstuetzerInnen a ' .
            'LEFT JOIN person b ON a.unterstuetzerIn_id = b.id WHERE a.antrag_id = ' . $antrag['id'];
        $unterstuetzerInnen = $dbOld->createCommand($sql)->queryAll();
        foreach ($unterstuetzerInnen as $unterstuetzerIn) {
            $dateResolution = ($unterstuetzerIn['beschlussdatum'] > 0 ? $unterstuetzerIn['beschlussdatum'] : null);
            $organisation   = $this->istOrganisation($unterstuetzerIn['name']);
            if ($unterstuetzerIn['beschlussdatum'] > 0) {
                $organisation = true;
            }
            $personType = ($organisation ? MotionSupporter::PERSON_ORGANIZATION : MotionSupporter::PERSON_NATURAL);

            $supporter                 = new MotionSupporter();
            $supporter->motionId       = $motion->id;
            $supporter->position       = $unterstuetzerIn['position'];
            $supporter->role           = static::$PERSON_ROLE_MAP[$unterstuetzerIn['rolle']];
            $supporter->name           = $unterstuetzerIn['name'];
            $supporter->organization   = $unterstuetzerIn['organisation'];
            $supporter->resolutionDate = $dateResolution;
            $supporter->contactEmail   = $unterstuetzerIn['kontakt_email'];
            $supporter->contactPhone   = $unterstuetzerIn['kontakt_telefon'];
            $supporter->personType     = $personType;
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
            }
        }


        $sql                = 'SELECT * FROM aenderungsantrag WHERE antrag_id = ' . $antrag['id'];
        $aenderungsantraege = $dbOld->createCommand($sql)->queryAll();
        foreach ($aenderungsantraege as $aenderungsantrag) {
            echo '- Amendment: ' . $aenderungsantrag['id'] . "\n";
            $this->migrateAmendment($dbOld, $aenderungsantrag, $motion);
        }
    }

    /**
     * @param Connection $dbOld
     * @param array $aenderungsantrag
     * @param Motion $motion
     */
    private function migrateAmendment(Connection $dbOld, $aenderungsantrag, Motion $motion)
    {
        $dateCreation   = ($aenderungsantrag['datum_einreichung'] > 0 ? $aenderungsantrag['datum_einreichung'] : null);
        $dateResolution = ($aenderungsantrag['datum_beschluss'] > 0 ? $aenderungsantrag['datum_beschluss'] : null);

        $amend              = new Amendment();
        $amend->motionId    = $motion->id;
        $amend->titlePrefix = $aenderungsantrag['revision_name'];
        /*
        $amend->changeMetatext        = $aenderungsantrag['aenderung_metatext'];
        $amend->changeText            = $aenderungsantrag['aenderung_text'];
        $amend->changeExplanation     = $aenderungsantrag['aenderung_begruendung'];
        $amend->changeExplanationHtml = $aenderungsantrag['aenderung_begruendung_html'];
        */
        $amend->changeMetatext        = '';
        $amend->changeText            = '';
        $amend->changeExplanation     = '';
        $amend->changeExplanationHtml = 0;
        $amend->cache                 = '';
        $amend->dateCreation          = $dateCreation;
        $amend->dateResolution        = $dateResolution;
        $amend->status                = static::$MOTION_STATUS_MAP[$aenderungsantrag['status']];
        $amend->statusString          = $aenderungsantrag['status_string'];
        $amend->noteInternal          = $aenderungsantrag['notiz_intern'];
        $amend->textFixed             = $aenderungsantrag['text_unveraenderlich'];
        if (!$amend->save()) {
            var_dump($amend->getErrors());
        }

        $sql                = 'SELECT * FROM aenderungsantrag_unterstuetzerInnen a ' .
            'LEFT JOIN person b ON a.unterstuetzerIn_id = b.id ' .
            'WHERE a.aenderungsantrag_id = ' . $aenderungsantrag['id'];
        $unterstuetzerInnen = $dbOld->createCommand($sql)->queryAll();
        foreach ($unterstuetzerInnen as $unterstuetzerIn) {
            $dateResolution = ($unterstuetzerIn['beschlussdatum'] > 0 ? $unterstuetzerIn['beschlussdatum'] : null);
            $organisation   = $this->istOrganisation($unterstuetzerIn['name']);
            if ($unterstuetzerIn['beschlussdatum'] > 0) {
                $organisation = true;
            }
            $personType = ($organisation ? MotionSupporter::PERSON_ORGANIZATION : MotionSupporter::PERSON_NATURAL);

            $supporter                 = new AmendmentSupporter();
            $supporter->amendmentId    = $amend->id;
            $supporter->position       = $unterstuetzerIn['position'];
            $supporter->role           = static::$PERSON_ROLE_MAP[$unterstuetzerIn['rolle']];
            $supporter->name           = $unterstuetzerIn['name'];
            $supporter->organization   = $unterstuetzerIn['organisation'];
            $supporter->resolutionDate = $dateResolution;
            $supporter->contactEmail   = $unterstuetzerIn['kontakt_email'];
            $supporter->contactPhone   = $unterstuetzerIn['kontakt_telefon'];
            $supporter->personType     = $personType;
            if (!$supporter->save()) {
                var_dump($supporter->getErrors());
            }
        }
    }

    /**
     * @param string $settingsFile
     * @param int $veranstaltungAlt
     * @param int $consultationNew
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionVeranstaltung($settingsFile = '', $veranstaltungAlt = 0, $consultationNew = 0)
    {
        if ($settingsFile != '') {
            $file     = file_get_contents($settingsFile);
            $file     = json_decode($file, true);
            $server   = $file['server'];
            $dbname   = $file['dbname'];
            $username = $file['username'];
            $password = $file['password'];
        } else {
            $server   = $this->prompt('DB-Server:');
            $dbname   = $this->prompt('DB Datenbank:');
            $username = $this->prompt('DB-Username:');
            $password = $this->prompt('DB-Passwort:');
        }

        if ($veranstaltungAlt == 0 || $consultationNew == 0) {
            $veranstaltungAlt = $this->prompt('Alte Veranstaltungs-ID:');
            $consultationNew  = $this->prompt('Neue Consultation-ID:');
        }

        $dbOld = new Connection([
            'dsn'      => 'mysql:host=' . $server . ';dbname=' . $dbname,
            'username' => $username,
            'password' => $password,
            'charset'  => 'utf8mb4',
        ]);
        $dbOld->open();

        $this->migrateConsultation($dbOld, $veranstaltungAlt, $consultationNew);

        /*
        if ($subdomain != '') {
            $where   = 'subdomain = "' . addslashes($subdomain) . '"';
            $command = $dbOld->createCommand('SELECT * FROM veranstaltungsreihe WHERE ' . $where);
        } else {
            $command = $dbOld->createCommand('SELECT * FROM veranstaltungsreihe');
        }
        $sites = $command->queryAll();
        foreach ($sites as $site) {
            $this->migrateSite($dbOld, $site, $newSite);
        }
        */
    }
}
