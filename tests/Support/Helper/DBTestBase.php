<?php
namespace Tests\Support\Helper;

use app\models\db\repostory\ConsultationRepository;
use Tests\config\AntragsgruenSetupDB;
use yii;

class DBTestBase extends TestBase
{
    use AntragsgruenSetupDB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDB();
        $file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Data/dbdata1.sql';
        $this->populateDB($file);

        yii::$app->db->close();

        // ConsultationRepository caches Consultation objects (and, transitively, whatever AR
        // relations get accessed on them, e.g. motionTypes/motionSections) in a process-static
        // array keyed by consultation ID. Since createDB() just rebuilt the whole database from
        // scratch with deterministic auto-increment IDs, an entry left over from an earlier test
        // would otherwise resolve to stale objects pointing at rows that no longer exist.
        ConsultationRepository::flushCache();
    }

    protected function tearDown(): void
    {
        $this->deleteDB();

        // PHPUnit keeps every test case object alive for the whole run, and the trait's $database
        // property holds this test's connection - so without letting go of it here, each finished
        // test leaves an open connection behind and a few hundred of them exhaust the server.
        $this->database->close();
        $this->database = null;

        parent::tearDown();
    }
}
