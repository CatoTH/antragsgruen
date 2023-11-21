<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class BinaryFileResponse implements ResponseInterface
{
    public const TYPE_PDF = 'pdf';
    public const TYPE_HTML = 'html';
    public const TYPE_ODS = 'ods';
    public const TYPE_ODT = 'odt';
    public const TYPE_XLSX = 'xlsx';
    public const TYPE_PNG = 'png';
    public const TYPE_JPEG = 'jpeg';
    public const TYPE_GIF = 'gif';
    public const TYPE_XML = 'xml';
    public const TYPE_ZIP = 'zip';
    public const TYPE_CSV = 'csv';
    public const TYPE_CSS = 'css';
    public const TYPE_YAML = 'yaml';

    private string $type;
    private string $content;
    private bool $download;
    private ?string $filename;
    private bool $robotsIndexable;
    private ?int $cacheSeconds;

    public function __construct(string $type, string $content, bool $download, ?string $filename, bool $robotsIndexable = false, ?int $cacheSeconds = null)
    {
        $this->type = $type;
        $this->content = $content;
        $this->download = $download;
        $this->filename = $filename;
        $this->robotsIndexable = $robotsIndexable;
        $this->cacheSeconds = $cacheSeconds;
    }

    public static function mimeTypeToType(string $mime): string
    {
        switch ($mime) {
            case 'image/png':
                return self::TYPE_PNG;
            case 'image/jpg':
            case 'image/jpeg':
            return self::TYPE_JPEG;
            case 'image/gif':
                return self::TYPE_GIF;
            default:
                return $mime;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function renderYii(Layout $layoutParams, Response $response): ?string
    {
        if (!$this->robotsIndexable) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($this->cacheSeconds) {
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $this->cacheSeconds));
            $response->headers->set('Pragma', 'cache');
            $response->headers->set('Cache-Control', 'public, max-age=' . (string)$this->cacheSeconds);
        }

        switch ($this->type) {
            case self::TYPE_ODT:
                $response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
                $fileEnding = 'odt';
                break;
            case self::TYPE_ODS:
                $response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
                $fileEnding = 'ods';
                break;
            case self::TYPE_XLSX:
                $response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $fileEnding = 'xlsx';
                break;
            case self::TYPE_PDF:
                $response->headers->add('Content-Type', 'application/pdf');
                $fileEnding = 'pdf';
                break;
            case self::TYPE_GIF:
                $response->headers->add('Content-Type', 'image/gif');
                $fileEnding = 'gif';
                break;
            case self::TYPE_JPEG:
                $response->headers->add('Content-Type', 'image/jpeg');
                $fileEnding = 'jpeg';
                break;
            case self::TYPE_PNG:
                $response->headers->add('Content-Type', 'application/png');
                $fileEnding = 'png';
                break;
            case self::TYPE_XML:
                $response->headers->add('Content-Type', 'application/xml');
                $fileEnding = 'xml';
                break;
            case self::TYPE_ZIP:
                $response->headers->add('Content-Type', 'application/zip');
                $fileEnding = 'zip';
                break;
            case self::TYPE_CSV:
                $response->headers->add('Content-Type', 'text/csv');
                $fileEnding = 'csv';
                break;
            case self::TYPE_CSS:
                $response->headers->add('Content-Type', 'text/css');
                $fileEnding = 'css';
                break;
            case self::TYPE_YAML:
                $response->headers->add('Content-Type', 'text/yaml');
                $fileEnding = 'yaml';
                break;
            default:
                $response->headers->add('Content-Type', 'text/html');
                $fileEnding = 'html';
        }
        $disposition = $this->download ? 'attachment' : 'inline';
        if ($this->filename) {
            $disposition .= ';filename="' . addslashes($this->filename) . '.' . $fileEnding . '"';
        }
        $response->headers->add('Content-disposition', $disposition);
        $response->format = Response::FORMAT_RAW;
        $response->data = $this->content;

        \Yii::$app->end();

        return null;
    }
}
