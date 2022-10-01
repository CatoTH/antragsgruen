<?php

declare(strict_types=1);

namespace app\models\http;

use app\models\settings\Layout;
use yii\web\Response;

class BinaryFileResponse implements ResponseInterface
{
    public const TYPE_PDF = 'pdf';
    public const TYPE_HTML = 'pdf';
    public const TYPE_ODS = 'ods';
    public const TYPE_XSLX = 'xlsc';

    private string $type;
    private string $content;
    private bool $download;
    private string $filename;

    public function __construct(string $type, string $content, bool $download, string $filename)
    {
        $this->type = $type;
        $this->content = $content;
        $this->download = $download;
        $this->filename = $filename;
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
        switch ($this->type) {
            case self::TYPE_ODS:
                $response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
                $response->headers->add('Content-disposition', 'filename="' . addslashes($this->filename) . '.ods"');
                break;
            case self::TYPE_XSLX:
                $response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $response->headers->add('Content-disposition', 'filename="' . addslashes($this->filename) . '.xslx"');
                break;
            default:
                $response->headers->add('Content-Type', 'text/html');
        }
        $response->format = Response::FORMAT_RAW;
        $response->data = $this->content;

        \Yii::$app->end();

        return null;
    }
}
