<?php
session_start();
$page_title = "Edit Kost";
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ibu_kost') {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/Storage.php';

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: kelola_kost.php");
    exit();
}

$id = $_GET['id'];

// Ambil data kost
$stmt = $conn->prepare("SELECT * FROM kost WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$kost = $stmt->fetch();

// Ambil galeri foto
$gStmt = $conn->prepare("SELECT id, file FROM kost_gambar WHERE kost_id = ? ORDER BY id");
$gStmt->execute([$id]);
$galeri = $gStmt->fetchAll();

if (!$kost) {
    $_SESSION['error'] = 'Kost tidak ditemukan!';
    header("Location: kelola_kost.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    csrf_verify('edit_kost.php?id=' . urlencode($id));

    $nama      = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $lokasi    = trim($_POST['lokasi'] ?? '');
    $harga     = str_replace('.', '', $_POST['harga'] ?? '');
    $fasilitas = trim($_POST['fasilitas'] ?? '');
    $aturan    = trim($_POST['aturan'] ?? '');
    $jenis     = $_POST['jenis_kost'] ?? 'campur';
    $kamar     = max(0, (int)($_POST['kamar_tersedia'] ?? 0));
    $gambar    = $kost['gambar']; // keep existing by default

    // Whitelist jenis kost
    if (!in_array($jenis, ['campur', 'putra', 'putri'], true)) {
        $jenis = 'campur';
    }

    // Helper validasi + simpan gambar (via Supabase Storage)
    function simpan_gambar_kost_edit(array $file, string $prefix): ?string {
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

    // Upload gambar utama baru (hapus yang lama)
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $res = simpan_gambar_kost_edit($_FILES['gambar'], 'm');
        if ($res === 'TOO_BIG') {
            $_SESSION['error'] = 'Ukuran gambar maksimal 2MB!';
            header("Location: edit_kost.php?id=$id");
            exit();
        } elseif ($res === 'BAD_TYPE') {
            $_SESSION['error'] = 'Format gambar tidak valid!';
            header("Location: edit_kost.php?id=$id");
            exit();
        } elseif ($res !== null) {
            // Hapus gambar lama di storage
            if (!empty($kost['gambar'])) {
                Storage::delete('kost', $kost['gambar']);
            }
            $gambar = $res;
        }
    }

    // Upload galeri tambahan baru
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
            $res = simpan_gambar_kost_edit($file, 'g');
            if ($res !== null && $res !== 'TOO_BIG' && $res !== 'BAD_TYPE') {
                $ins = $conn->prepare("INSERT INTO kost_gambar (kost_id, file) VALUES (?, ?)");
                $ins->execute([$id, $res]);
            }
        }
    }

    // Hapus galeri yang dicentang
    $hapusGaleri = $_POST['hapus_galeri'] ?? [];
    if (!empty($hapusGaleri) && is_array($hapusGaleri)) {
        foreach ($hapusGaleri as $gid) {
            $gid = (int)$gid;
            $gs = $conn->prepare("SELECT file FROM kost_gambar WHERE id = ? AND kost_id = ?");
            $gs->execute([$gid, $id]);
            $rowG = $gs->fetch();
            if ($rowG) {
                Storage::delete('kost', $rowG['file']);
                $gd = $conn->prepare("DELETE FROM kost_gambar WHERE id = ? AND kost_id = ?");
                $gd->execute([$gid, $id]);
            }
        }
    }

    if (empty($nama) || empty($lokasi) || empty($harga)) {
        $_SESSION['error'] = 'Nama, lokasi, dan harga wajib diisi!';
    } else {
        $stmt = $conn->prepare("UPDATE kost SET nama=?, deskripsi=?, lokasi=?, harga=?, fasilitas=?, jenis_kost=?, kamar_tersedia=?, gambar=?, aturan=? WHERE id=? AND user_id=?");
        $ok = $stmt->execute([$nama, $deskripsi, $lokasi, $harga, $fasilitas, $jenis, $kamar, $gambar, $aturan, $id, $user_id]);
        if ($ok) {
            $_SESSION['success'] = 'Kost berhasil diperbarui!';
            header("Location: kelola_kost.php");
            exit();
        } else {
            $_SESSION['error'] = 'Gagal memperbarui kost.';
        }
    }
}
?>
<div class="page-title">
    <h1>✏️ Edit Kost</h1>
    <p>Perbarui informasi kost kamu</p>
</div>

<div class="form-card">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <div class="form-group">
            <label for="nama">Nama Kost</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($kost['nama']); ?>" required>
        </div>
        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($kost['deskripsi']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="lokasi">Lokasi</label>
            <input type="text" id="lokasi" name="lokasi" value="<?php echo htmlspecialchars($kost['lokasi']); ?>" required>
        </div>
        <div class="form-group">
            <label for="harga">Harga Sewa per Bulan</label>
            <input type="text" id="harga" name="harga" value="<?php echo $kost['harga']; ?>" required oninput="this.value = this.value.replace(/[^0-9]/g,'')">
        </div>
        <div class="form-group">
            <label for="gambar">Foto Kost (Utama)</label>
            <?php if (!empty($kost['gambar'])): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?php echo Storage::url('kost', $kost['gambar']); ?>" alt="Foto kost" style="width:160px;height:120px;object-fit:cover;border-radius:10px;">
                    <br><small style="color:#64748b;">Foto saat ini. Upload di bawah untuk ganti.</small>
                </div>
            <?php endif; ?>
            <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
            <small style="color:#64748b;">Format: JPG, PNG, WebP. Kosongkan jika tidak ingin ganti foto.</small>
        </div>

        <?php if (count($galeri) > 0): ?>
        <div class="form-group">
            <label>Foto Galeri Saat Ini (centang untuk hapus)</label>
            <div class="gallery-grid" style="margin-top:8px;">
                <?php foreach ($galeri as $g): ?>
                <label style="position:relative; cursor:pointer;">
                    <img src="<?php echo Storage::url('kost', $g['file']); ?>" alt="Foto galeri" style="width:100%; height:130px; object-fit:cover; border-radius:10px;">
                    <input type="checkbox" name="hapus_galeri[]" value="<?php echo $g['id']; ?>" style="position:absolute; top:6px; left:6px; width:18px; height:18px; accent-color:#ef4444;">
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="galeri">Tambah Foto Galeri Baru</label>
            <input type="file" id="galeri" name="galeri[]" accept="image/jpeg,image/png,image/webp" multiple>
            <small style="color:#64748b;">Bisa pilih beberapa sekaligus. Maks 2MB per foto</small>
        </div>
        <div class="form-group">
            <label for="fasilitas">Fasilitas</label>
            <input type="text" id="fasilitas" name="fasilitas" value="<?php echo htmlspecialchars($kost['fasilitas']); ?>">
            <small style="color:#64748b;">Pisahkan dengan koma</small>
        </div>
        <div class="form-group">
            <label for="aturan">Aturan Kost</label>
            <textarea id="aturan" name="aturan" placeholder="Contoh: Tidak boleh membawa hewan peliharaan. Jam malam 22.00."><?php echo htmlspecialchars($kost['aturan'] ?? ''); ?></textarea>
            <small style="color:#64748b;">Satu aturan per baris (opsional)</small>
        </div>
        <div class="form-group">
            <label for="jenis_kost">Jenis Kost</label>
            <select id="jenis_kost" name="jenis_kost">
                <option value="campur" <?php echo $kost['jenis_kost'] === 'campur' ? 'selected' : ''; ?>>Campur</option>
                <option value="putra" <?php echo $kost['jenis_kost'] === 'putra' ? 'selected' : ''; ?>>Putra</option>
                <option value="putri" <?php echo $kost['jenis_kost'] === 'putri' ? 'selected' : ''; ?>>Putri</option>
            </select>
        </div>
        <div class="form-group">
            <label for="kamar_tersedia">Jumlah Kamar Tersedia</label>
            <input type="number" id="kamar_tersedia" name="kamar_tersedia" value="<?php echo $kost['kamar_tersedia']; ?>" min="0">
        </div>
        <div class="form-actions">
            <button type="submit" name="update" class="btn btn-primary btn-lg">💾 Simpan Perubahan</button>
            <a href="kelola_kost.php" class="btn btn-outline btn-lg">Batal</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
