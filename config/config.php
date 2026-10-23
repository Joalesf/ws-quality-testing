<?php
declare(strict_types=1);

const APP_NAME = 'ServiceHub Bus';
const APP_VERSION = '1.0.0';
const APP_TIMEZONE = 'America/Panama';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'bus_web_services';
const DB_USER = 'root';
const DB_PASS = '';

// Credenciales privadas en local.php o variables de entorno.
if (is_file(__DIR__ . '/local.php')) require __DIR__ . '/local.php';
if (!defined('ADMIN_USER')) define('ADMIN_USER', getenv('APP_ADMIN_USER') ?: 'admin');
if (!defined('ADMIN_PASSWORD')) define('ADMIN_PASSWORD', getenv('APP_ADMIN_PASSWORD') ?: '');
if (!defined('TESTER_USER')) define('TESTER_USER', getenv('APP_TESTER_USER') ?: 'tester');
if (!defined('TESTER_PASSWORD')) define('TESTER_PASSWORD', getenv('APP_TESTER_PASSWORD') ?: '');

date_default_timezone_set(APP_TIMEZONE);

