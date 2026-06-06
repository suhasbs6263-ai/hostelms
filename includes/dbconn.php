<?php
require_once(__DIR__ . '/../config/app.php');

$host = "localhost";
$user = "root";
$password = "";
$db = "hostelms";

$mysqli = @new mysqli($host, $user, $password);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Unable to create or access database: " . $mysqli->error);
}

if (!$mysqli->select_db($db)) {
    die("Unable to select database: " . $mysqli->error);
}

require_once(__DIR__ . '/schema-bootstrap.php');
require_once(__DIR__ . '/production-bootstrap.php');
$schemaBootstrapReport = schema_bootstrap($mysqli);
$productionBootstrapRan = ensure_production_schema($mysqli);
?>
