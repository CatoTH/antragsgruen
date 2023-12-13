<?php

namespace app\models\forms;

use app\models\db\User;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use yii\base\Model;
use yii\db\Connection;

class AntragsgruenInitDb extends Model
{
    use AntragsgruenInitConfigwriteTrait;

    public string $language = 'en';

    public string $sqlType        = 'mysql';
    public ?string $sqlHost = null;
    public ?string $sqlUsername = null;
    public ?string $sqlPassword = null;
    public ?int $sqlPort        = 3306;
    public ?string $sqlDB = null;
    public string $sqlTablePrefix = '';

    public ?bool $prettyUrls;

    public ?string $adminUsername = null;
    public ?string $adminPassword = null;

    public bool $sqlCreateTables = true;

    /** @var int[] */
    public ?array $adminIds = null;
    public ?User $adminUser = null;


    public function __construct(string $configFile)
    {
        parent::__construct();
        $config = $this->readConfigFromFile($configFile);
        if ($this->databaseParamsComeFromEnv()) {
            $this->setMysqlFromEnv();
        } else {
            $this->setDatabaseFromParams($config->dbConnection);
        }
        $this->adminIds = ($config->adminUserIds ?: []);
        $this->language = $config->baseLanguage;
    }

    public function rules(): array
    {
        return [
            [['sqlType', 'adminUsername', 'adminPassword'], 'required'],
            [['sqlType', 'sqlHost', 'sqlUsername', 'sqlDB', 'sqlCreateTables'], 'safe'],
            [['adminUsername', 'adminPassword', 'language'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);

        if (isset($values['sqlPassword']) && $values['sqlPassword'] != '') {
            $this->sqlPassword = $values['sqlPassword'];
        } elseif (isset($values['sqlPasswordNone'])) {
            $this->sqlPassword = '';
        }
        $this->sqlCreateTables = isset($values['sqlCreateTables']);

        if (isset($values['prettyUrls']) && $values['prettyUrls'] === '0') {
            $this->prettyUrls = false;
        } else {
            $this->prettyUrls = true;
        }
        if (str_contains($this->sqlHost, ':')  ) {
            list($host, $port) = explode(':', $this->sqlHost);
            $this->sqlHost = $host;
            $this->sqlPort = IntVal($port);
        }
    }

    public function databaseParamsComeFromEnv(): bool
    {
        return isset($_ENV['ANTRAGSGRUEN_MYSQL_USER']) && isset($_ENV['ANTRAGSGRUEN_MYSQL_PASSWORD']) &&
            isset($_ENV['ANTRAGSGRUEN_MYSQL_HOST']) && isset($_ENV['ANTRAGSGRUEN_MYSQL_DB']);
    }

    private function setMysqlFromEnv(): void
    {
        $this->sqlUsername = $_ENV['ANTRAGSGRUEN_MYSQL_USER'];
        $this->sqlPassword = $_ENV['ANTRAGSGRUEN_MYSQL_PASSWORD'];
        $this->sqlDB       = $_ENV['ANTRAGSGRUEN_MYSQL_DB'];
        $this->sqlHost     = $_ENV['ANTRAGSGRUEN_MYSQL_HOST'];
        $this->sqlType     = 'mysql';
        if (isset($_ENV['ANTRAGSGRUEN_MYSQL_PORT'])) {
            $this->sqlPort = IntVal($_ENV['ANTRAGSGRUEN_MYSQL_PORT']);
        }
    }

    private function setDatabaseFromParams(?array $params): void
    {
        if (!is_array($params) || !isset($params['dsn'])) {
            return;
        }
        if (isset($params['username'])) {
            $this->sqlUsername = $params['username'];
        }
        if (isset($params['password'])) {
            $this->sqlPassword = $params['password'];
        }

        $parts = explode(':', $params['dsn']);
        if (count($parts) !== 2) {
            return;
        }
        $this->sqlType = $parts[0];
        $params        = explode(';', $parts[1]);
        for ($i = 0; $i < count($params); $i++) {
            $parts = explode('=', $params[$i]);
            if (count($parts) === 2) {
                if ($parts[0] === 'dbname') {
                    $this->sqlDB = $parts[1];
                }
                if ($parts[0] === 'host') {
                    $this->sqlHost = $parts[1];
                }
                if ($parts[0] === 'port' && $parts[1] > 0) {
                    $this->sqlPort = (int)$parts[1];
                }
            }
        }
    }


    /**
     * @throws Internal
     */
    protected function getDBConfig(): array
    {
        if ($this->sqlType == 'mysql') {
            $dsn = 'mysql:host=' . $this->sqlHost . ';dbname=' . $this->sqlDB;
            if ($this->sqlPort !== 3306) {
                $dsn .= ';port=' . $this->sqlPort;
            }
            return [
                'dsn'            => $dsn,
                'emulatePrepare' => true,
                'username'       => $this->sqlUsername,
                'password'       => $this->sqlPassword,
                'charset'        => 'utf8mb4',
            ];
        }
        throw new Internal('Unknown SQL Type');
    }

    public function overwriteYiiConnection(): void
    {
        $connConfig          = $this->getDBConfig();
        $connConfig['class'] = Connection::class;
        \Yii::$app->set('db', $connConfig);
    }

    public function overwritePrettyUrls(): void
    {
        \Yii::$app->urlManager->enablePrettyUrl = $this->prettyUrls;
    }


    /**
     * @throws Internal
     * @throws \Exception
     */
    public function verifyDBConnection(bool $exceptions = true): bool
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new Connection($connConfig);
            $connection->createCommand('SHOW TABLES')->queryAll();
            return true;
        } catch (\yii\db\Exception $e) {
            switch ($e->getCode()) {
                case 1044:
                    $message = 'The database login is correct, however I could not connect to the actual database.
                    Maybe a permission problem?';
                    break;
                case 1045:
                    $message = 'Invalid database username or password';
                    break;
                case 1046:
                    $message = 'Invalid database name entered';
                    break;
                case 2002:
                    if (mb_stripos($e->getMessage(), 'Connection refused')) {
                        $message = 'Database: Connection refused';
                    } elseif (mb_stripos($e->getMessage(), 'getaddrinfo failed')) {
                        $message = 'Database hostname not found';
                    } else {
                        $message = 'Could not connect to database: ' . $e->getMessage();
                    }
                    break;
                default:
                    if (mb_stripos($e->getMessage(), 'No database selected') !== false) {
                        $message = 'Invalid or no database name entered';
                    } else {
                        $message = 'Unknown error when trying to connect to database: ' . $e->getMessage();
                    }
            }
            if ($exceptions) {
                throw new \Exception($message);
            } else {
                return false;
            }
        }
    }

    public function tablesAreCreated(): bool
    {
        try {
            $connConfig = $this->getDBConfig();
            $connection = new Connection($connConfig);
            $tables     = $connection->createCommand('SHOW TABLES')->queryAll();
            $found      = false;
            foreach ($tables as $table) {
                if (in_array('site', $table)) {
                    $found = true;
                }
            }
            return $found;
        } catch (\yii\db\Exception $e) {
            return false;
        }
    }

    public function createTables(): void
    {
        $connConfig = $this->getDBConfig();
        $connection = new Connection($connConfig);

        $createString = (string)file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'create.sql'
        );
        $createString = str_replace('###TABLE_PREFIX###', $this->sqlTablePrefix, $createString);
        $command      = $connection->createCommand($createString);
        $command->execute();

        $createString = (string)file_get_contents(
            \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR .
            'db' . DIRECTORY_SEPARATOR . 'data.sql'
        );
        $createString = str_replace('###TABLE_PREFIX###', $this->sqlTablePrefix, $createString);
        $command      = $connection->createCommand($createString);
        $command->execute();
    }

    public function isConfigured(): bool
    {
        return file_exists($this->configFile) && $this->tablesAreCreated();
    }

    public function hasAdminAccount(): bool
    {
        return (count($this->adminIds) > 0);
    }

    public function getAdminUser(): ?User
    {
        if (count($this->adminIds) == 0) {
            return null;
        }
        return User::findOne($this->adminIds[0]);
    }


    public function createOrUpdateAdminAccount(): void
    {
        /** @var User|null $user */
        $user = User::findOne(['auth' => 'email:' . $this->adminUsername]);
        if ($user) {
            $user->pwdEnc = password_hash($this->adminPassword, PASSWORD_DEFAULT);
            if (!$user->save()) {
                var_dump($user->getErrors());
                die();
            }
            if (!in_array($user->id, $this->adminIds)) {
                $this->adminIds[] = $user->id;
            }
            $this->adminUser = $user;
        } else {
            $this->createAdminAccount();
        }
    }

    public function createAdminAccount(): void
    {
        $user                  = new User();
        $user->auth            = 'email:' . $this->adminUsername;
        $user->status          = User::STATUS_CONFIRMED;
        $user->email           = $this->adminUsername;
        $user->emailConfirmed  = 1;
        $user->pwdEnc          = password_hash($this->adminPassword, PASSWORD_DEFAULT);
        $user->name            = '';
        $user->organizationIds = '';
        if (!$user->save()) {
            var_dump($user->getErrors());
            die();
        }
        $this->adminIds[] = $user->id;
        $this->adminUser  = $user;
    }

    /**
     * @throws Internal
     */
    protected function setConfigValues(AntragsgruenApp $config): void
    {
        $config->dbConnection = $this->getDBConfig();
        $config->adminUserIds = $this->adminIds;
        $config->baseLanguage = $this->language;
        $config->prettyUrl    = $this->prettyUrls;
    }
}
