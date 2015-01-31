<?php
/**
 * Application configuration shared by all test types
 */
$base_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

return [
    'components' => [
        'db'         => require(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
        'mailer'     => [
            'useFileTransport' => true,
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
    ],
    'params'     => [
        'sql_test_schema_create' => $base_dir . 'docs/schema_create.sql',
        'sql_test_schema_delete' => $base_dir . 'docs/schema_delete.sql'
    ]
];
