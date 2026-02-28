<?php
// logout.php
session_start();

// unset session variables
$_SESSION = array();

// remove cookies
if (ini_get("session.use_cookies")) {
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $cookieParams["path"], $cookieParams["domain"],
        $cookieParams["secure"], $cookieParams["httponly"]
    );
}

// remove sesion
session_destroy();

// redir to login
header("Location: login.php");
exit();
?>