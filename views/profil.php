<?php
session_start();
$page_title = "Profil Saya";
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/Storage.php';
$user_id = $_SESSION['user_id'];

// --- Update profil ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    csrf_verify('profil.php');

    $nama  = trim($_POST['nama'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (empty($nama)) {
        $_SESSION['error'] = 'Nama tidak boleh kosong!';
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama = ?, no_hp = ? WHERE id = ?");
        $stmt->execute([$nama, $no_hp, $user_id]);
        $_SESSION['nama'] = $nama;
        $_SESSION['success'] = 'Profil berhasil diperbarui!';
    }
    header("Location: profil.php");
    exit();
}

// --- Ganti password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    csrf_verify('profil.php');

    $old = $_POST['password_lama'] ?? '';
    $new = $_POST['password_baru'] ?? '';
    $confirm = $_POST['password_konfirmasi'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($old, $user['password'])) {
        $_SESSION['error'] = 'Password lama salah!';
    } elseif (strlen($new) < 6) {
        $_SESSION['error'] = 'Password baru minimal 6 karakter!';
    } elseif ($new !== $confirm) {
        $_SESSION['error'] = 'Konfirmasi password tidak cocok!';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $up->execute([$hashed, $user_id]);
        $_SESSION['success'] = 'Password berhasil diganti!';
    }
    header("Location: profil.php");
    exit();
}

// --- Upload foto profil ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    csrf_verify('profil.php');

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = 'Ukuran foto maksimal 2MB!';
            header("Location: profil.php");
            exit();
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['foto']['tmp_name']);
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $namaFile = time() . '_' . uniqid() . '.' . $ext;

            // Hapus foto lama dari storage (kalau ada dan bukan default)
            $oldStmt = $conn->prepare("SELECT foto FROM users WHERE id = ?");
            $oldStmt->execute([$user_id]);
            $oldFoto = $oldStmt->fetch()['foto'] ?? '';
            if (!empty($oldFoto)) {
                Storage::delete('profil', $oldFoto);
            }

            if (Storage::upload('profil', $namaFile, $_FILES['foto']['tmp_name'], $mime)) {
                $up = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
                $up->execute([$namaFile, $user_id]);
                $_SESSION['foto'] = $namaFile;
                $_SESSION['success'] = 'Foto profil berhasil diupload!';
            } else {
                $_SESSION['error'] = 'Gagal mengupload foto.';
            }
        } else {
            $_SESSION['error'] = 'Format foto tidak valid! (JPG/PNG/WebP)';
        }
    } else {
        $_SESSION['error'] = 'Pilih file foto terlebih dahulu.';
    }
    header("Location: profil.php");
    exit();
}

// --- Ambil data profil ---
$stmt = $conn->prepare("SELECT nama, email, no_hp, foto FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profil = $stmt->fetch();
?>
<div class="page-title">
    <h1>👤 Profil Saya</h1>
    <p>Kelola informasi akun kamu</p>
</div>

<div class="container" style="max-width:720px; margin:0 auto;">
    <!-- Foto Profil -->
    <div class="form-card">
        <h3>Foto Profil</h3>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
            <?php if (!empty($profil['foto'])): ?>
                <img src="<?php echo Storage::url('profil', $profil['foto']); ?>" alt="Foto profil" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
            <?php else: ?>
                <div style="width:80px; height:80px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:32px;">👤</div>
            <?php endif; ?>
            <span style="color:#64748b;">JPG, PNG, atau WebP. Maks 2MB.</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <div class="form-group">
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" name="upload_foto" class="btn btn-primary">📷 Upload Foto</button>
        </form>
    </div>

    <!-- Data Profil -->
    <div class="form-card">
        <h3>Data Profil</h3>
        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($profil['nama']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($profil['email']); ?>" disabled>
                <small style="color:#64748b;">Email tidak bisa diubah.</small>
            </div>
            <div class="form-group">
                <label for="no_hp">No. Handphone</label>
                <input type="text" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($profil['no_hp']); ?>" placeholder="0812xxxxxx">
            </div>
            <button type="submit" name="update_profil" class="btn btn-primary">💾 Simpan Profil</button>
        </form>
    </div>

    <!-- Ganti Password -->
    <div class="form-card">
        <h3>🔒 Ganti Password</h3>
        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label for="password_lama">Password Lama</label>
                <input type="password" id="password_lama" name="password_lama" required>
            </div>
            <div class="form-group">
                <label for="password_baru">Password Baru</label>
                <input type="password" id="password_baru" name="password_baru" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label for="password_konfirmasi">Konfirmasi Password Baru</label>
                <input type="password" id="password_konfirmasi" name="password_konfirmasi" required>
            </div>
            <button type="submit" name="ganti_password" class="btn btn-primary">🔑 Ganti Password</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>