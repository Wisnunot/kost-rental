<?php
// =============================================================
// Kost-Rental — Database Connection (Supabase PostgreSQL)
// Baca env: SUPABASE_HOST / SUPABASE_PORT / SUPABASE_DB /
//           SUPABASE_USER / SUPABASE_PASSWORD
// Sumber: environment (Railway) ATAU file .env (lokal, gitignored)
// =============================================================

function env(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val === false || $val === '') {
        static $env = null;
        if ($env === null) {
            $env = [];
            $file = __DIR__ . '/../.env';
            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (str_starts_with(trim($line), '#')) continue;
                    [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                    $env[trim($k)] = trim($v);
                }
            }
        }
        $val = $env[$key] ?? '';
    }
    return $val !== '' ? $val : $default;
}

$host = env('SUPABASE_HOST', '');
$port = env('SUPABASE_PORT', '5432');
$db   = env('SUPABASE_DB', 'postgres');
$user = env('SUPABASE_USER', 'postgres');
$pass = env('SUPABASE_PASSWORD', '');

if ($host === '' || $pass === '') {
    die('Koneksi Gagal: SUPABASE_HOST / SUPABASE_PASSWORD belum di-set (cek .env atau environment)');
}

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);
} catch (PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}