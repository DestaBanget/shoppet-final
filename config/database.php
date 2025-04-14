<?php
// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'shoppet_db';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set UTF-8 character set
$conn->set_charset("utf8mb4");
