<?php
function getDbConnection() {
    $servername = "mysql";
    $username = "root";
    $password = "password";
    $database = "gebruiker";

    try {
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die($e->getMessage());
    }
}
?>