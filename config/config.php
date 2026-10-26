<?php
// Sinhala: App-wide constants
define('BASE_URL', 'http://localhost/playground-booking/');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');

date_default_timezone_set('Asia/Colombo');

// Error reporting (dev only — production එකේදී off කරන්න)
ini_set('display_errors', 1);
error_reporting(E_ALL);