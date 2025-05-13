
<?php
session_start();

// Check if we need to clear any API tokens
if (isset($_SESSION['api_token'])) {
    unset($_SESSION['api_token']);
}

// Clear all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
