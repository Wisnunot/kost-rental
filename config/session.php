<?php
// =============================================================
// Kost-Rental — Session & Security Headers
// Panggil di paling atas setiap entry point PENGGANTI session_start().
//   require_once __DIR__ . '/../config/session.php';
// Memberikan:
//   1. Cookie session ber-flag HttpOnly + Secure + SameSite=Lax
//   2. Security headers (X-Frame-Options, nosniff, Referrer-Policy, dst)
//   3. Session timeout idle 30 menit (untuk user yang lupa logout)
// Catatan: Secure flag hanya dipakai saat request via HTTPS (produksi),
//          supaya testing lokal http://localhost tetap jalan.
// =============================================================

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? '') == 443;

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,   // kirim cookie hanya lewat HTTPS di produksi
        'httponly' => true,       // JavaScript tidak bisa baca cookie -> anti XSS theft
        'samesite' => 'Lax',      // mitigasi CSRF lintas situs
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('KOSTSESSID'); // ganti nama default PHPSESSID
    session_start();
}

// --- Idle timeout: paksa login ulang setelah 30 menit tanpa aktivitas ---
$IDLE_LIMIT = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $IDLE_LIMIT) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// --- Security headers (aman untuk semua response HTML) ---
if (!headers_sent()) {
    header('X-Frame-Options: DENY');                       // anti clickjacking
    header('X-Content-Type-Options: nosniff');             // anti MIME sniffing
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-XSS-Protection: 0');                         // modern: CSP yang bertugas; filter lama justru berbahaya
}
