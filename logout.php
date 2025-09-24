<?php
// logout.php
session_start();

// Clear all session data
$_SESSION = [];

// Destroy session completely
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>