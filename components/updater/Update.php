<?php

namespace app\components\updater;

class Update
{
    private const TYPE_PATCH = 'patch';

    public string $type;
    public string $version;
    public string $changelog;
    public string $url;
    public int $filesize;
    public string $signature;

    private ?UpdatedFiles $updatedFiles = null;

    /**
     * @throws \Exception
     */
    public function __construct(array $json)
    {
        $this->type      = $json['type'];
        $this->version   = $json['version'];
        $this->changelog = $json['changelog'];
        $this->url       = $json['url'];
        $this->filesize  = $json['filesize'];
        $this->signature = $json['signature'];

        if ($this->type !== self::TYPE_PATCH) {
            throw new \Exception('Only patch releases are supported');
        }
    }

    private function getBasePath(): string
    {
        return __DIR__ . '/../../';
    }

    private function getAbsolutePath(): string
    {
        $dir = $this->getBasePath() . 'runtime/updates/';
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }
        $base = explode('/', $this->url);
        return $dir . $base[count($base) - 1];
    }

    /**
     * @throws \Exception
     */
    private function getBackupPath(string $version): string
    {
        $dir = $this->getBasePath() . 'runtime/backups/' . $version . '/';
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception('Could not create backup directory');
            }
        }
        return $dir;
    }

    public function checkDownloadIntegrity(string $content): bool
    {
        if (extension_loaded('sodium')) {
            return (base64_encode(sodium_crypto_generichash($content)) === $this->signature);
        } else {
            // The polyfill uses more than a hundred MB even for a update file less than a MB, thus crashing the system.
            // So we skip the integrity check here, as this check is not necessary for the overall security:
            // the actual integrity check is not based on the hash, but on signing the contained update.json.
            return true;
        }
    }

    public function isDownloaded(): bool
    {
        if (!file_exists($this->getAbsolutePath())) {
            return false;
        }
        $content = file_get_contents($this->getAbsolutePath());
        if (!is_string($content)) {
            return false;
        }
        return $this->checkDownloadIntegrity($content);
    }

    /**
     * @throws \Exception
     */
    public function download(): void
    {
        $curlc = curl_init($this->url);
        if (!$curlc) {
            throw new \Exception('The update could not be loaded (curl cannot be initialized)');
        }
        curl_setopt($curlc, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curlc);
        $info = curl_getinfo($curlc);
        curl_close($curlc);

        if ($info['http_code'] !== 200 || !is_string($resp)) {
            throw new \Exception('The update could not be loaded');
        }

        if (!$this->checkDownloadIntegrity($resp)) {
            throw new \Exception('The update file has the wrong checksum');
        }

        file_put_contents($this->getAbsolutePath(), $resp);
    }

    /**
     * @throws \Exception
     */
    public function readUpdateJson(): UpdatedFiles
    {
        if (!$this->updatedFiles) {
            if (!$this->isDownloaded()) {
                throw new \Exception('File is not yet downloaded');
            }

            $zipfile = new \ZipArchive();
            if ($zipfile->open($this->getAbsolutePath()) !== true) {
                throw new \Exception('Could not open the ZIP file');
            }

            $updateJson = $zipfile->getFromName('update.json');
            $signature = $zipfile->getFromName('update.json.signature');
            if (!$updateJson || !$signature) {
                throw new \Exception('Could not get update.json or update.json.signature from the ZIP file');
            }
            /** @var non-empty-string $updateSignature */
            $updateSignature = base64_decode($signature);
            /** @var non-empty-string $publicKey */
            $publicKey       = base64_decode((string)file_get_contents(__DIR__ . '/../../config/update-public.key'));
            if (!sodium_crypto_sign_verify_detached($updateSignature, $updateJson, $publicKey)) {
                throw new \Exception('The signature of the update file is invalid');
            }

            $zipfile->close();

            $this->updatedFiles = new UpdatedFiles($updateJson);
        }

        return $this->updatedFiles;
    }

    /**
     * @throws \Exception
     */
    public function backupOldFiles(string $version): void
    {
        $basepath = $this->getBackupPath($version);
        $filesObj = $this->readUpdateJson();
        $files    = array_merge(array_keys($filesObj->files_updated), $filesObj->files_deleted);
        foreach ($files as $file) {
            $fulldir = $basepath . (dirname($file) === '.' ? '' : dirname($file));
            if (!file_exists($fulldir) && !mkdir($fulldir, 0755, true)) {
                throw new \Exception('Could not create backup sub-directory: ' .
                    htmlentities($fulldir, ENT_COMPAT, 'UTF-8'));
            }
            if (!file_exists($this->getBasePath() . $file)) {
                throw new \Exception('An expected file of the current version was not found: ' .
                    htmlentities($file, ENT_COMPAT, 'UTF-8'));
            }
            if (!copy($this->getBasePath() . $file, $fulldir . DIRECTORY_SEPARATOR . basename($file))) {
                throw new \Exception('Could not back up file: ' . htmlentities($file, ENT_COMPAT, 'UTF-8'));
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function checkRequirements(array $requirements): void
    {
        if (isset($requirements['php']) && substr($requirements['php'], 0, 2) === '>=') {
            $minVersion = substr($requirements['php'], 2);
            if (!version_compare(PHP_VERSION, $minVersion, '>=')) {
                throw new \Exception('This version needs a PHP version of at least: ' . $minVersion .
                    ' (' . PHP_VERSION . ' is installed)');
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function verifyFileIntegrityAndPermissions(?string $version = null): void
    {
        $filesObj = $this->readUpdateJson();

        $this->checkRequirements($filesObj->requirements);

        $zipfile = new \ZipArchive();
        if ($zipfile->open($this->getAbsolutePath()) !== true) {
            throw new \Exception('Could not open the ZIP file');
        }

        if ($version !== null && $filesObj->from_version !== $version) {
            throw new \Exception('The loaded update file does not match the current version (' .
                $filesObj->from_version . ' vs. ' . $version . ').');
        }

        if (extension_loaded('sodium')) {
            $fileList  = array_merge($filesObj->files_added, $filesObj->files_updated);
            $corrupted = [];
            foreach ($fileList as $file => $correctHash) {
                $content = $zipfile->getFromName($file);
                if (!$content) {
                    throw new \Exception('Could not get file from ZIP file: ' . $file);
                }
                $zipHash = base64_encode(sodium_crypto_generichash($content));
                if ($zipHash !== $correctHash) {
                    $corrupted[] = $file;
                }
            }
        } else {
            $fileList  = array_merge($filesObj->files_added_md5, $filesObj->files_updated_md5);
            $corrupted = [];
            foreach ($fileList as $file => $correctHash) {
                $content = $zipfile->getFromName($file);
                if (!$content) {
                    throw new \Exception('Could not get file from ZIP file: ' . $file);
                }
                $zipHash = md5($content);
                if ($zipHash !== $correctHash) {
                    $corrupted[] = $file;
                }
            }
        }

        $fileList = array_merge($filesObj->files_deleted, array_keys($filesObj->files_updated));
        $notFound = array_filter($fileList, function ($file) {
            return !file_exists($this->getBasePath() . $file);
        });

        $alreadyFound = array_filter(array_keys($filesObj->files_added), function ($file) {
            return file_exists($this->getBasePath() . $file);
        });

        $notWritable = array_merge(
            array_filter($filesObj->files_deleted, function ($file) {
                return !is_writable($this->getBasePath() . $file);
            }),
            array_filter(array_keys($filesObj->files_updated), function ($file) {
                return !is_writable($this->getBasePath() . $file);
            }),
            // For added files, we check if the directory is writable.
            // If the directory does not exist yet, we check the parent directory, recursively
            array_filter(array_map(function ($file) {
                return dirname($file);
            }, array_keys($filesObj->files_added)), function ($file) {
                while (!file_exists($this->getBasePath() . $file) && $file !== '.') {
                    $file = dirname($file);
                }
                return !is_writable($this->getBasePath() . $file);
            })
        );

        $notWritable = array_filter($notWritable, function ($file) use ($notFound) {
            return !in_array($file, $notFound);
        });
        $zipfile->close();

        $filesListToUl = function ($files) {
            return '<ul>' . implode("\n", array_map(function ($file) {
                    return '<li>' . htmlentities($file, ENT_COMPAT, 'UTF-8') . '</li>';
                }, $files)) . '</ul>';
        };

        if (count($corrupted) > 0 || count($notFound) > 0 || count($alreadyFound) > 0 || count($notWritable) > 0) {
            $errors = '';
            if (count($corrupted) > 0) {
                $errors .= '<p>The files in the backup file seem to be corrupted:</p>' .
                    $filesListToUl($corrupted) . "\n";
            }
            if (count($notFound) > 0) {
                $errors .= '<p>The following files were not found in the current installation:</p>' .
                    $filesListToUl($notFound) . "\n";
            }
            if (count($alreadyFound) > 0) {
                $errors .= '<p>The following files to be created already exist:</p>' .
                    $filesListToUl($alreadyFound) . "\n";
            }
            if (count($notWritable) > 0) {
                $errors .= '<p>The following files / directories do not have writing permissions:</p>' .
                    $filesListToUl($notWritable) . "\n";
            }
            throw new \Exception($errors);
        }
    }

    protected function createDirectoriesRecursively(string $dir): void
    {
        if ($dir === '.' || file_exists($this->getBasePath() . $dir)) {
            return;
        }
        $this->createDirectoriesRecursively(dirname($dir));
        mkdir($this->getBasePath() . $dir, 0755);
    }

    /**
     * @throws \Exception
     */
    public function performUpdate(): void
    {
        $filesObj = $this->readUpdateJson();

        $zipfile = new \ZipArchive();
        if ($zipfile->open($this->getAbsolutePath()) !== true) {
            throw new \Exception('Could not open the ZIP file');
        }

        $fileList = array_merge($filesObj->files_added, $filesObj->files_updated);
        foreach (array_keys($fileList) as $file) {
            $content = $zipfile->getFromName($file);
            $this->createDirectoriesRecursively(dirname($file));
            if (file_put_contents($this->getBasePath() . $file, $content) === false) {
                throw new \Exception('The file could not be updated: ' . htmlentities($file, ENT_COMPAT, 'UTF-8'));
            }
        }

        foreach ($filesObj->files_deleted as $file) {
            if (!unlink($this->getBasePath() . $file)) {
                throw new \Exception('The file could not be deleted: ' . htmlentities($file, ENT_COMPAT, 'UTF-8'));
            }
        }

        $zipfile->close();
    }
}
