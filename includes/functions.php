<?php
// Common helper functions

// Sanitize user input
function sanitize($conn, $input) {
    return $conn->real_escape_string(trim($input));
}

// Display success message
function showSuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

// Display error message
function showError($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message) . '</div>';
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'success';
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        if ($type === 'success') {
            return showSuccess($message);
        } else {
            return showError($message);
        }
    }
    return '';
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
