<?php
// SMART CACHE CONTROL
header("Cache-Control: no-cache, public, must-revalidate");

session_start();

define('ADMIN_PASS', 'Shivaji@007');
define('COOKIE_NAME', 'av_admin_token');
define('COOKIE_EXPIRY', time() + (86400 * 30)); // 30 Days

function checkAuth() {
    // 1. Check Session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // 2. Check Cookie (Persistent Login)
    if (isset($_COOKIE[COOKIE_NAME])) {
        if (password_verify(ADMIN_PASS, $_COOKIE[COOKIE_NAME])) {
            $_SESSION['logged_in'] = true;
            return true;
        }
    }

    // 3. Not Authenticated
    header("Location: login.php");
    exit;
}

function login($password, $remember = false) {
    if ($password === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        
        if ($remember) {
            // Store hashed password in cookie (Simple mechanism for this scale)
            // ideally we'd use a random token + database, but this matches requirements
            $hash = password_hash(ADMIN_PASS, PASSWORD_DEFAULT);
            setcookie(COOKIE_NAME, $hash, COOKIE_EXPIRY, "/", "", false, true);
        }
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    setcookie(COOKIE_NAME, "", time() - 3600, "/");
    header("Location: login.php");
    exit;
}

// Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}
?>
