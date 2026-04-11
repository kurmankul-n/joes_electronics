<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = "localhost";
$username = "root";
$password = "";
$database = "inventory_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("<h2 style='color:red;font-family:Arial'>
         Database connection failed: " . mysqli_connect_error() . "
         </h2>");
}
?>