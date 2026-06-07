<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\settings\AntragsgruenApp;
use app\models\http\RestApiResponse;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class ErrorTrackingController extends Base
{
    public function actionJs(): RestApiResponse
    {
        $app = AntragsgruenApp::getInstance();
        if ($app->jsErrorTracking === null) {
            return new RestApiResponse(400, null, '{"success": false, "error": "disabled"}');
        }

        $parts = parse_url($app->jsErrorTracking);
        if (!isset($parts['scheme']) && isset($parts['path'])) {
            $parts['scheme'] = 'file';
        }
        if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['file', 'otel'])) {
            return new RestApiResponse(500, null, '{"success": true, "error": "error tracking not set up correctly"}');
        }

        $raw = $this->getPostBody();
        if (!$raw) {
            return new RestApiResponse(400, null, '{"success": false, "error": "no content"}');
        }

        $data = json_decode($raw, true);
        if (!$data || json_last_error() !== JSON_ERROR_NONE) {
            return new RestApiResponse(400, null, '{"success": false, "error": "could not parse content"}');
        }

        if ($parts['scheme'] == 'file') {
            if (!isset($parts['path'])) {
                return new RestApiResponse(500, null, '{"success": true, "error": "error tracking not set up correctly"}');
            }
            if (!$this->isWritableOrCreatable($parts['path'])) {
                return new RestApiResponse(500, null, '{"success": true, "error": "error log not writable"}');
            }
            file_put_contents($app->jsErrorTracking, $raw . "\n", FILE_APPEND);
        }

        if ($parts['scheme'] == 'otel') {
            /** @phpstan-ignore-next-line */
            if (!class_exists(Globals::class, true)) {
                return new RestApiResponse(500, null, '{"success": true, "error": "OpenTelemetry is not installed"}');
            }

            $tracer = Globals::tracerProvider()->getTracer('frontend-errors');

            $span = $tracer->spanBuilder($parts['host'] ?? 'js.error')
                            /** @phpstan-ignore-next-line */
                           ->setSpanKind(SpanKind::KIND_SERVER)
                           ->startSpan();

            $scope = $span->activate();
            try {
                $span->setAttributes([
                    'error.type'            => $data['type'] ?? 'Error',
                    'error.message'         => $data['message'] ?? 'Unknown error',
                    'error.stack'           => $data['stack'] ?? '',
                    'browser.url'           => $data['url'] ?? '',
                    'browser.user_agent'    => $data['userAgent'] ?? '',
                    'code.filepath'         => $data['filename'] ?? '',
                    'code.lineno'           => $data['lineno'] ?? 0,
                    'telemetry.source'      => 'js',
                ]);

                /** @phpstan-ignore-next-line */
                $span->setStatus(StatusCode::STATUS_ERROR, $data['message'] ?? '');

                $span->recordException(new \RuntimeException(
                    $data['message'] ?? 'JS Error',
                ), [
                    'exception.type'       => $data['type'] ?? 'Error',
                    'exception.stacktrace' => $data['stack'] ?? '',
                ]);
            } finally {
                $span->end();
                $scope->detach();
            }
        }

        return new RestApiResponse(200, null, '{"success": true}');
    }

    private function isWritableOrCreatable(string $path): bool
    {
        if (file_exists($path)) {
            return is_writable($path);
        }

        $dir = dirname($path);

        return is_dir($dir) && is_writable($dir);
    }
}
