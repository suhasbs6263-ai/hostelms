<?php
$host = "localhost";     // server name
$user = "root";          // default username
$password = "";          // default password is empty
$db = "hostelms";        // your database name

$mysqli = @new mysqli($host, $user, $password);

// check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Unable to create or access database: " . $mysqli->error);
}

if (!$mysqli->select_db($db)) {
    die("Unable to select database: " . $mysqli->error);
}

require_once(__DIR__ . '/schema-bootstrap.php');
$schemaBootstrapReport = schema_bootstrap($mysqli);
?>
