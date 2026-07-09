<?php

declare(strict_types=1);

namespace app\models\sectionTypes;

/**
 * Reference to a file uploaded through a web form ($_FILES tmp_name), passed to
 * Image/PDF section types instead of the file content itself.
 *
 * Security: strings reaching setMotionData()/setAmendmentData() are always treated as
 * base64-encoded content, never as file paths. Only server-side code can construct this
 * object; deserialized API requests cannot, so API clients can never make the section
 * types read an arbitrary local file.
 */
final class UploadedFileRef
{
    public function __construct(
        public readonly string $path,
    ) {
    }
}
