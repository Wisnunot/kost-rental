<?php
// =============================================================
// CSRF Protection Helper
// =============================================================
// Fungsi:
//  - csrf_token()       : generate/simpan token di session, return token
//  - csrf_field()       : echo <input type="hidden" name="csrf_token">
//  - csrf_verify()      : validasi $_POST['csrf_token'] vs session
//                         (jika gagal: redirect + flash error, exit)
//  - verify_post()      : cek method POST + token valid (untuk handler)
//
// Dipakai di semua form POST + aksi destruktif (hapus/konfirmasi/tolak/batal).
// Setiap halaman yang render form memanggil csrf_field() di dalam <form>.
// Setiap handler POST memanggil csrf_verify() di awal sebelum proses.
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validasi token CSRF. Jika gagal -> redirect ke fallback + flash error, exit.
 */
function csrf_verify(string $fallback = 'login.php'): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        $_SESSION['error'] = 'Sesi tidak valid. Silakan coba lagi.';
        header('Location: ' . $fallback);
        exit();
    }
}

/**
 * Cek bahwa request adalah POST dan token CSRF valid.
 * Dipakai di awal handler POST.
 */
function verify_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: login.php');
        exit();
    }
    csrf_verify();
}