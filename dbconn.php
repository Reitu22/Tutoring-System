<?php
try {
    $pdo = new PDO(dsn: "mysql:host=localhost;port = 3306;dbname=tutoring_system",
    username: "root", password: "Reitumetse123*");
    echo "The database is Online! " . "<br>";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
