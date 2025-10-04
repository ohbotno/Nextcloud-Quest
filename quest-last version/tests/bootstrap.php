<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';

// Fix for "Autoload path not allowed" error
\OC_App::loadApp('nextcloudquest');

if (!class_exists('PHPUnit\Framework\TestCase')) {
    require_once 'PHPUnit/Autoload.php';
}