<?php

$host = 'localhost';
$database = 'tutoring_system';
$username = 'root';
$password = '';
$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE =>                PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE =>     PDO::FETCH_ASSOC, 
    PDO::ATTR_EMULATE_PREPARES =>       false
    ];

try { 
    $pdo = new PDO($dsn, $username, $password, $options); 
} catch (PDOException $exception) { 
    http_response_code(500); 
    exit('Database connection failed. Check php/config/database.php and ensure MySQL is running.'); 
}

?>