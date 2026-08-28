<?php

namespace app\controllers;

use app\models\db\Amendment;
use app\models\db\Site;
use app\models\settings\AntragsgruenApp;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Test-only endpoints for Playwright e2e tests.
 *
 * Gated by:
 *   1. YII_ENV === 'test'  (env check; throws RuntimeException on prod)
 *   2. IP allowlist         (defense-in-depth; throws HttpException 403)
 *
 * Replaces the DB-lifecycle logic that the legacy Codeception
 * `AntragsgruenSetupDB` trait did in-process, so the e2e suite can manage
 * fixtures via HTTP without bootstrapping a Yii web Application in Node.
 *
 * Endpoints (all POST, JSON response):
 *   /test/populate-db          {fixture: "dbdata1"|"dbdata-yfj"|"dbdata-dbwv"}
 *   /test/reset-db
 *   /test/set-config           {key, value}
 *   /test/url-builder          {route, params(JSON)}
 *   /test/set-api-enabled      {subdomain, consultationUrl, enabled}
 *   /test/set-amendment-status {subdomain, consultationUrl, id, status}
 *   /test/set-user-fixed-data  {subdomain, consultationUrl, email, nameGiven,
 *                               nameFamily, organisation, fixed}
 *   /test/user-votes           {subdomain, consultationUrl, email, votingBlock,
 *                               itemId, answer}
 *   /test/totp-code            {}  → returns current TOTP code for the test user
 *
 * DB connection attribute PDO::MYSQL_ATTR_MULTI_STATEMENTS is set
 * explicitly so the multi-statement fixture SQL loads regardless of
 * server-side multi_statements flag.
 */
class TestController extends Controller
{
    public $enableCsrfValidation = false;

    private const ALLOWED_IPS = [
        '127.0.0.1',
        '::1',
        'localhost',
    ];

    private const ALLOWED_CIDRS = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];

    public function init(): void
    {
        parent::init();
        if (YII_ENV !== 'test') {
            throw new \RuntimeException(
                'TestController is only available when YII_ENV=test. ' .
                'Refusing to expose test-only endpoints to a production environment.'
            );
        }
        if (!$this->isRemoteIpAllowed()) {
            throw new \yii\web\HttpException(403, 'TestController: remote IP not in allowlist');
        }
    }

    public function beforeAction($action): bool
    {
        $this->getHttpResponse()->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    private function isRemoteIpAllowed(): bool
    {
        $remoteIp = $this->getHttpRequest()->remoteIP;
        if (in_array($remoteIp, self::ALLOWED_IPS, true)) {
            return true;
        }
        foreach (self::ALLOWED_CIDRS as $cidr) {
            if ($this->ipInCidr($remoteIp, $cidr)) {
                return true;
            }
        }
        return false;
    }

    private function getHttpRequest(): \yii\web\Request
    {
        /** @var \yii\web\Request $request */
        $request = Yii::$app->request;
        return $request;
    }

    private function getHttpResponse(): \yii\web\Response
    {
        /** @var \yii\web\Response $response */
        $response = Yii::$app->response;
        return $response;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, ':')) {
            return false;
        }
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $mask = -1 << (32 - (int)$bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    public function actionPopulateDb(): array
    {
        $fixture = $this->getHttpRequest()->post('fixture', 'dbdata1');
        $allowed = ['dbdata1', 'dbdata-yfj', 'dbdata-dbwv'];
        if (!in_array($fixture, $allowed, true)) {
            return ['ok' => false, 'error' => "Unknown fixture: $fixture"];
        }
        try {
            $this->createDb();
            $this->populateDb(__DIR__ . '/../tests/Support/Data/' . $fixture . '.sql');
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionResetDb(): array
    {
        try {
            $this->deleteDb();
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionSetConfig(): array
    {
        $key = (string)$this->getHttpRequest()->post('key', '');
        $value = $this->getHttpRequest()->post('value');
        if ($key === '') {
            return ['ok' => false, 'error' => 'Missing key'];
        }
        $configFile = Yii::$app->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config_tests.json';
        if (!is_writable($configFile)) {
            return ['ok' => false, 'error' => "Config file not writable: $configFile"];
        }
        $config = json_decode((string)file_get_contents($configFile), true);
        if (!is_array($config) || count($config) === 0) {
            return ['ok' => false, 'error' => 'Config file invalid'];
        }
        $config[$key] = $value;
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        return ['ok' => true];
    }

    public function actionUrlBuilder(): array
    {
        $route = $this->getHttpRequest()->post('route', '');
        $paramsJson = $this->getHttpRequest()->post('params', '{}');
        $params = json_decode($paramsJson, true) ?: [];
        if (is_string($route)) {
            $params[0] = $route;
        } elseif (is_array($route) && isset($route[0])) {
            $params = array_merge($route, $params);
        }
        try {
            $url = Yii::$app->getUrlManager()->createUrl($params);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        return ['ok' => true, 'url' => $url];
    }

    public function actionSetApiEnabled(): array
    {
        $subdomain = (string)$this->getHttpRequest()->post('subdomain', 'stdparteitag');
        $consultationUrl = (string)$this->getHttpRequest()->post('consultationUrl', 'std-parteitag');
        $enabled = (string)$this->getHttpRequest()->post('enabled', '1') === '1';
        try {
            $site = Site::findOne(['subdomain' => $subdomain]);
            if (!$site) {
                return ['ok' => false, 'error' => "Site not found: $subdomain"];
            }
            $settings = $site->getSettings();
            $settings->apiEnabled = $enabled;
            $site->setSettings($settings);
            $site->save();
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionSetAmendmentStatus(): array
    {
        $id = (int)$this->getHttpRequest()->post('id', 0);
        $status = (int)$this->getHttpRequest()->post('status', 0);
        $amendment = Amendment::findOne($id);
        if (!$amendment) {
            return ['ok' => false, 'error' => "Amendment not found: $id"];
        }
        $amendment->status = $status;
        $amendment->save();
        return ['ok' => true];
    }

    public function actionSetUserFixedData(): array
    {
        $email = (string)$this->getHttpRequest()->post('email', '');
        if ($email === '') {
            return ['ok' => false, 'error' => 'Missing email'];
        }
        $user = \app\models\db\User::findOne(['email' => $email]);
        if (!$user) {
            return ['ok' => false, 'error' => "User not found: $email"];
        }
        $given = $this->getHttpRequest()->post('nameGiven', null);
        if ($given !== null) {
            $user->nameGiven = (string)$given;
        }
        $family = $this->getHttpRequest()->post('nameFamily', null);
        if ($family !== null) {
            $user->nameFamily = (string)$family;
        }
        $organisation = $this->getHttpRequest()->post('organization', null);
        if ($organisation !== null) {
            $user->organization = (string)$organisation;
        }
        $fixed = $this->getHttpRequest()->post('fixed', '0');
        if ($fixed === '1') {
            $user->fixedData = 1;
        }
        $user->save();
        return ['ok' => true];
    }

    public function actionUserVotes(): array
    {
        $email = (string)$this->getHttpRequest()->post('email', '');
        $votingBlockId = (int)$this->getHttpRequest()->post('votingBlock', 0);
        $itemId = (int)$this->getHttpRequest()->post('itemId', 0);
        $answer = (string)$this->getHttpRequest()->post('answer', '');
        $user = \app\models\db\User::findOne(['email' => $email]);
        if (!$user) {
            return ['ok' => false, 'error' => "User not found: $email"];
        }
        $vote = \app\models\db\Vote::find()
            ->andWhere(['votingBlockId' => $votingBlockId, 'motionId' => $itemId, 'userId' => $user->id])
            ->one();
        if (!$vote) {
            $vote = new \app\models\db\Vote();
            $vote->votingBlockId = $votingBlockId;
            $vote->motionId = $itemId;
            $vote->userId = $user->id;
        }
        $vote->vote = $this->answerToInt($answer);
        $vote->save();
        return ['ok' => true];
    }

    private function answerToInt(string $answer): int
    {
        return match (strtolower($answer)) {
            'yes', 'present' => \app\models\votings\AnswerTemplates::VOTE_YES,
            'no' => \app\models\votings\AnswerTemplates::VOTE_NO,
            'abstention' => \app\models\votings\AnswerTemplates::VOTE_ABSTENTION,
            default => (int)$answer,
        };
    }

    public function actionTotpCode(): array
    {
        try {
            $user = \app\models\db\User::findOne(['email' => 'testadmin@example.org']);
            if (!$user) {
                return ['ok' => false, 'error' => 'test admin user not found'];
            }
            if (empty($user->twoFactorAuthSecret)) {
                return ['ok' => false, 'error' => 'user has no 2FA secret configured'];
            }
            $otp = \OTPHP\TOTP::createFromSecret($user->twoFactorAuthSecret);
            $code = $otp->now();
            return ['ok' => true, 'code' => $code];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private ?\yii\db\Connection $database = null;
    private ?string $databaseDelete = null;

    private function createDb(): void
    {
        $this->database = Yii::$app->db;
        $init = (string)file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $init = str_replace('###TABLE_PREFIX###', '', $init);
        $data = (string)file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $data = str_replace('###TABLE_PREFIX###', '', $data);
        $this->databaseDelete = (string)file_get_contents(
            Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'delete.sql'
        );
        $this->databaseDelete = str_replace('###TABLE_PREFIX###', '', $this->databaseDelete);

        $this->deleteDb();
        $this->executeMultiStatementSql($init);
        $this->executeMultiStatementSql($data);
        $this->database->getSchema()->refresh();
    }

    private function deleteDb(): void
    {
        if ($this->database && $this->databaseDelete) {
            $this->executeMultiStatementSql($this->databaseDelete);
        }
    }

    private function populateDb(string $file): void
    {
        $testdata = (string)file_get_contents($file);
        $testdata = str_replace('###TABLE_PREFIX###', '', $testdata);
        $this->executeMultiStatementSql($testdata);
        $this->database->createCommand('UPDATE user SET settings = NULL WHERE id <= 3')->execute();
    }

    private function executeMultiStatementSql(string $sql): void
    {
        if (trim($sql) === '') {
            return;
        }
        $pdo = $this->database->getMasterPdo();
        $attribute = defined('Pdo\\Mysql::ATTR_MULTI_STATEMENTS')
            ? \Pdo\Mysql::ATTR_MULTI_STATEMENTS
            : \PDO::MYSQL_ATTR_MULTI_STATEMENTS;
        $pdo->setAttribute($attribute, true);
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        do {
        } while ($stmt->nextRowset());
    }
}
