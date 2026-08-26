<?php
require_once '../config/session.php';
$page_title = "Tambah Kost";
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ibu_kost') {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/Storage.php';

// Proses tambah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    csrf_verify('tambah_kost.php');

    $nama      = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $lokasi    = trim($_POST['lokasi'] ?? '');
    $harga     = str_replace('.', '', $_POST['harga'] ?? '');
    $fasilitas = trim($_POST['fasilitas'] ?? '');
    $aturan    = trim($_POST['aturan'] ?? '');
    $jenis     = $_POST['jenis_kost'] ?? 'campur';
    $kamar     = max(0, (int)($_POST['kamar_tersedia'] ?? 1));
    $user_id   = $_SESSION['user_id'];
    $gambar    = '';   // foto utama
    $galeri    = [];   // foto tambahan

    // Whitelist jenis kost
    if (!in_array($jenis, ['campur', 'putra', 'putri'], true)) {
        $jenis = 'campur';
    }

    // Helper validasi + simpan gambar (via Supabase Storage)
    function simpan_gambar_kost(array $file, string $prefix): ?string {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] > 2 * 1024 * 1024) return 'TOO_BIG';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowedMime[$mime])) return 'BAD_TYPE';
        $namaFile = $prefix . time() . '_' . uniqid() . '.' . $allowedMime[$mime];
        if (!Storage::upload('kost', $namaFile, $file['tmp_name'], $mime)) return null;
        return $namaFile;
    }

    // Upload gambar utama
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $res = simpan_gambar_kost($_FILES['gambar'], 'm');
        if ($res === 'TOO_BIG') {
            $_SESSION['error'] = 'Ukuran gambar maksimal 2MB!';
            header("Location: tambah_kost.php");
            exit();
        } elseif ($res === 'BAD_TYPE') {
            $_SESSION['error'] = 'Format gambar tidak valid!';
            header("Location: tambah_kost.php");
            exit();
        } elseif ($res !== null) {
            $gambar = $res;
        }
    }

    // Upload galeri tambahan
    if (!empty($_FILES['galeri']['name'][0])) {
        foreach ($_FILES['galeri']['name'] as $i => $n) {
            if ($_FILES['galeri']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name'     => $n,
                'type'     => $_FILES['galeri']['type'][$i],
                'tmp_name' => $_FILES['galeri']['tmp_name'][$i],
                'error'    => $_FILES['galeri']['error'][$i],
                'size'     => $_FILES['galeri']['size'][$i],
            ];
            $res = simpan_gambar_kost($file, 'g');
            if ($res !== null && $res !== 'TOO_BIG' && $res !== 'BAD_TYPE') {
                $galeri[] = $res;
            }
        }
    }

    if (empty($nama) || empty($lokasi) || empty($harga)) {
        $_SESSION['error'] = 'Nama, lokasi, dan harga wajib diisi!';
    } else {
        $stmt = $conn->prepare("INSERT INTO kost (user_id, nama, deskripsi, lokasi, harga, fasilitas, jenis_kost, kamar_tersedia, gambar, aturan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $nama, $deskripsi, $lokasi, $harga, $fasilitas, $jenis, $kamar, $gambar, $aturan]);
        $kostId = (int)$conn->lastInsertId();

        // Simpan galeri
        if ($kostId > 0 && count($galeri) > 0) {
            $ins = $conn->prepare("INSERT INTO kost_gambar (kost_id, file) VALUES (?, ?)");
            foreach ($galeri as $g) {
                $ins->execute([$kostId, $g]);
            }
        }

        if ($kostId > 0) {
            $_SESSION['success'] = 'Kost berhasil ditambahkan!';
            header("Location: kelola_kost.php");
            exit();
        } else {
            $_SESSION['error'] = 'Gagal menambahkan kost.';
        }
    }
}
?>
<div class="page-title">
    <h1>➕ Tambah Kost Baru</h1>
    <p>Daftarkan properti kost kamu</p>
</div>

<div class="form-card">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="nama">Nama Kost *</label>
            <input type="text" id="nama" name="nama" placeholder="Contoh: Kost Mawar Indah" required>
        </div>
        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Jelaskan tentang kost kamu..."></textarea>
        </div>
        <div class="form-group">
            <label for="lokasi">Lokasi *</label>
            <input type="text" id="lokasi" name="lokasi" placeholder="Contoh: Jl. Merdeka No.10, Bandung" required>
        </div>
        <div class="form-group">
            <label for="harga">Harga Sewa per Bulan *</label>
            <input type="text" id="harga" name="harga" placeholder="Contoh: 1500000" required oninput="this.value = this.value.replace(/[^0-9]/g,'')">
            <small style="color:#64748b;">Cukup angka saja, contoh: 1500000</small>
        </div>
        <div class="form-group">
            <label for="gambar">Foto Kost (Utama)</label>
            <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
            <small style="color:#64748b;">Format: JPG, PNG, WebP. Maksimal 2MB</small>
        </div>
        <div class="form-group">
            <label for="galeri">Foto Galeri (boleh banyak)</label>
            <input type="file" id="galeri" name="galeri[]" accept="image/jpeg,image/png,image/webp" multiple>
            <small style="color:#64748b;">Bisa pilih beberapa foto sekaligus (Ctrl+klik). Maks 2MB per foto</small>
        </div>
        <div class="form-group">
            <label for="fasilitas">Fasilitas</label>
            <input type="text" id="fasilitas" name="fasilitas" placeholder="Pisahkan dengan koma. Contoh: WiFi, AC, Parkir">
            <small style="color:#64748b;">Pisahkan dengan koma</small>
        </div>
        <div class="form-group">
            <label for="aturan">Aturan Kost</label>
            <textarea id="aturan" name="aturan" placeholder="Contoh: Tidak boleh membawa hewan peliharaan. Jam malam 22.00. Dilarang merokok di dalam kamar."></textarea>
            <small style="color:#64748b;">Satu aturan per baris (opsional)</small>
        </div>
        <div class="form-group">
            <label for="jenis_kost">Jenis Kost</label>
            <select id="jenis_kost" name="jenis_kost">
                <option value="campur">Campur</option>
                <option value="putra">Putra</option>
                <option value="putri">Putri</option>
            </select>
        </div>
        <div class="form-group">
            <label for="kamar_tersedia">Jumlah Kamar Tersedia</label>
            <input type="number" id="kamar_tersedia" name="kamar_tersedia" value="1" min="1">
        </div>
        <div class="form-actions">
            <button type="submit" name="simpan" class="btn btn-primary btn-lg">💾 Simpan Kost</button>
            <a href="kelola_kost.php" class="btn btn-outline btn-lg">Batal</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
