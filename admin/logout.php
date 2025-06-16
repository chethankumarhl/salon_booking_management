<?php
// Use the same session name used during admin login
session_name("admin_session");
session_start();

// Unset only admin-specific session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

// Optionally clear admin cookies too
setcookie("admin_id", "", time() - 3600, "/");
setcookie("admin_username", "", time() - 3600, "/");

// Destroy the session
session_destroy();

// Redirect to admin login page
header("Location: admin_login.php");
exit();
?>
