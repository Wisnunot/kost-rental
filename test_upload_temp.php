<?php
// =============================================================
// TEST UPLOAD GALERI — simulasi POST multipart via PHP (fixed)
// =============================================================
$base = 'http://localhost:8080';
$ck = sys_get_temp_dir() . '/ck_upload2.txt';
@unlink($ck);

function request($url, $ck, $post = null) {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $ck,
        CURLOPT_COOKIEFILE => $ck,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
    ];
    if ($post !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $post;
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $body = substr($resp, $info['header_size'] ?? 0);
    return [$info, $body];
}

// 1. Ambil token login + login
[, $html] = request($base . '/views/login.php', $ck);
preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m);
$tokLogin = $m[1] ?? null;
[$info] = request($base . '/controllers/auth.php', $ck, http_build_query([
    'login' => 1, 'email' => 'ibu@sari.com', 'password' => '123456', 'csrf_token' => $tokLogin,
]));
echo "login -> HTTP {$info['http_code']} redirect={$info['redirect_url']}\n";

// 2. Ambil token edit page (harus sudah login)
[, $html] = request($base . '/views/edit_kost.php?id=2', $ck);
preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m);
$tokEdit = $m[1] ?? null;
echo "edit token: " . substr($tokEdit ?? 'NULL', 0, 10) . "\n";

if (!$tokEdit) {
    echo "FAIL: tidak bisa ambil token edit (sesi gagal?)\n";
    exit(1);
}

// 3. POST update + upload galeri
[$info, $body] = request($base . '/views/edit_kost.php?id=2', $ck, [
    'update' => '1',
    'nama' => 'Kost Melati Putih',
    'deskripsi' => 'Kost putra eksklusif dengan suasana tenang. Cocok untuk mahasiswa.',
    'lokasi' => 'Jl. Diponegoro No.25, Jakarta',
    'harga' => '2000000',
    'fasilitas' => 'WiFi, AC, Dapur Bersama, Parkir Motor, Air',
    'aturan' => "Khusus putra\nDilarang membawa teman menginap",
    'csrf_token' => $tokEdit,
    'galeri[]' => new CURLFile('C:/laragon/www/kost-rental/uploads/kost/_test_galeri.jpg', 'image/jpeg', 'gal-tulip-test.jpg'),
]);
echo "edit+upload -> HTTP {$info['http_code']} redirect={$info['redirect_url']}\n";

// 4. Verifikasi galeri id=2
[, $html] = request($base . '/views/detail_kost.php?id=2', $ck);
preg_match_all('/gal-[a-z]+\.jpg/', $html, $m);
echo "galeri id=2: " . implode(', ', array_unique($m[0] ?? [])) . "\n";
echo "aturan tampil: " . (strpos($html, 'Khusus putra') !== false ? 'YA' : 'TIDAK') . "\n";
