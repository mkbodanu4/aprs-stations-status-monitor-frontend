<?php

if (!file_exists(".env"))
    die('Application configuration not found.');

// Parse dotenv file
$env = file(".env");
foreach ($env as $row) {
    $matches = array();

    if (preg_match("/^(?!#)([A-Za-z_]{1,})\=(.*?)$/si", $row, $matches)) {
        if (!putenv($matches[1] . "=" . trim($matches[2], "\""))) {
            die('Fatal error while parsing configuration, please check configuration file.');
        }
    }
}

switch (getenv("ENVIRONMENT")) {
    case "production":
        error_reporting(NULL);
        ini_set("display_errors", "0");
        break;
    default:
        error_reporting(E_ALL);
        ini_set("display_errors", "1");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(getenv('MYSQL_HOSTNAME'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
} catch (Exception $e) {
    die('Database connection not established, please check application configuration');
}

include __DIR__ . DIRECTORY_SEPARATOR . 'aprs_fi.class.php';

$aprs_fi = new aprs_fi(getenv('APRS_FI_API_KEY'), getenv('API_USER_AGENT'));

