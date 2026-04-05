<?php
require_once 'includes/db_config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Streaming Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="index.php">🎬 Streaming Platform</a>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Content</a></li>
            <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
            <li><a href="ratings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'ratings.php' ? 'class="active"' : ''; ?>>Ratings</a></li>
            <li><a href="subscriptions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'subscriptions.php' ? 'class="active"' : ''; ?>>Subscriptions</a></li>
        </ul>
    </nav>
    <main class="container">
        <?php echo displayFlashMessage(); ?>
