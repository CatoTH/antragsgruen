<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */

define('ANTRAGSGRUEN_VERSION', '3.9.0a1');
define('ANTRAGSGRUEN_HISTORY_URL', 'https://github.com/CatoTH/antragsgruen/blob/v3/History.md');
define('ANTRAGSGRUEN_UPDATE_BASE', 'https://antragsgruen.de/updates/');

// For PHPExcel
define('PCLZIP_TEMPORARY_DIR', $params->tmpDir);
