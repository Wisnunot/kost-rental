<?php
// =============================================================
// TEST HAPUS GALERI — via PHP curl (file-based, reliable)
// =============================================================
$base = 'http://localhost:8080';
$ck = __DIR__ . '/_ck_test.txt';
@unlink($ck);

function req2($url, $ck, $post = null) {
    $ch = curl_init($url);
    $o = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $ck,
        CURLOPT_COOKIEFILE => $ck,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($post !== null) {
        $o[CURLOPT_POST] = true;
        $o[CURLOPT_POSTFIELDS] = $post;
    }
    curl_setopt_array($ch, $o);
    $r = curl_exec($ch);
    $i = curl_getinfo($ch);
    return [$r, $i['redirect_url']];
}

// 1. Login
[, $h] = req2("$base/views/login.php", $ck);
preg_match('/name="csrf_token" value="([^"]+)"/', $h, $m);
$tok = $m[1] ?? 'NULL';
echo "1. token login: " . substr($tok, 0, 8) . " (page len=" . strlen($h) . ")\n";

[, $redir] = req2("$base/controllers/auth.php", $ck, http_build_query([
    'login' => 1, 'email' => 'ibu@sari.com', 'password' => '123456', 'csrf_token' => $tok,
]));
echo "2. login redirect: $redir\n";

// 2. Edit page token
[, $h2] = req2("$base/views/edit_kost.php?id=2", $ck);
preg_match('/name="csrf_token" value="([^"]+)"/', $h2, $m2);
$tokEdit = $m2[1] ?? 'NULL';
echo "3. edit token: " . substr($tokEdit, 0, 8) . " (len=" . strlen($h2) . ")\n";

// 3. Hapus galeri id=7
[, $redir2] = req2("$base/views/edit_kost.php?id=2", $ck, http_build_query([
    'update' => '1',
    'nama' => 'Kost Melati Putih',
    'deskripsi' => 'Kost putra eksklusif dengan suasana tenang. Cocok untuk mahasiswa.',
    'lokasi' => 'Jl. Diponegoro No.25, Jakarta',
    'harga' => '2000000',
    'fasilitas' => 'WiFi, AC, Dapur Bersama, Parkir Motor, Air',
    'aturan' => "Khusus putra\nDilarang membawa teman menginap",
    'hapus_galeri' => ['7'],
    'csrf_token' => $tokEdit,
]));
echo "4. hapus galeri redirect: $redir2\n";

// 4. Verifikasi DB + file
require __DIR__ . '/config/database.php';
$q = $conn->query("SELECT id, kost_id, file FROM kost_gambar WHERE kost_id = 2 ORDER BY id");
$rows = $q->fetchAll();
echo "5. galeri kost 2 sekarang: " . count($rows) . " item\n";
foreach ($rows as $r) echo "   - id={$r['id']} file={$r['file']}\n";
$fileTest = __DIR__ . '/uploads/kost/g1786442850_6a7af462f04c4.jpg';
echo "6. file test galeri: " . (file_exists($fileTest) ? "MASIH ADA (gagal hapus)" : "terhapus OK") . "\n";

@unlink($ck);
