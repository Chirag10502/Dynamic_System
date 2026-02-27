<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "dynamic_db";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

?>