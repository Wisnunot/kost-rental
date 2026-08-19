<?php
// =============================================================
// LOGOUT — via POST + CSRF (anti logout-CSRF)
// =============================================================
require_once '../config/csrf.php';

csrf_verify('login.php'); // tolak GET / token asing

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: login.php');
exit();