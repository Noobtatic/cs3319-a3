<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'ta');           // 根据需要修改
define('DB_PASS', 'cs3319');       // 根据需要修改
define('DB_NAME', 'hsuo3_streamingdb');  // 改成你的数据库名

// Create database connection
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Close database connection
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
