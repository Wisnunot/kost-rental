<?php
require_once '../config/session.php';
$page_title = "Detail Kost";
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/Storage.php';

if (!isset($_GET['id'])) {
    header("Location: list_kost.php");
    exit();
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM kost WHERE id = ?");
$stmt->execute([$id]);
$kost = $stmt->fetch();

if (!$kost) {
    echo "<div class='container' style='text-align:center;padding:80px 20px;'><h2>Kost tidak ditemukan!</h2><a href='list_kost.php' class='btn btn-primary mt-20'>Kembali</a></div>";
    include 'footer.php';
    exit();
}

// Ambil galeri foto
$gStmt = $conn->prepare("SELECT file FROM kost_gambar WHERE kost_id = ? ORDER BY id");
$gStmt->execute([$id]);
$galeri = array_column($gStmt->fetchAll(), 'file');

// Semua gambar untuk galeri (utama + tambahan)
$semuaFoto = [];
if (!empty($kost['gambar'])) $semuaFoto[] = $kost['gambar'];
foreach ($galeri as $f) {
    if ($f !== $kost['gambar']) $semuaFoto[] = $f;
}

// Nama pemilik untuk info kontak
$owner = null;
if (!empty($kost['user_id'])) {
    $oS = $conn->prepare("SELECT nama, no_hp FROM users WHERE id = ?");
    $oS->execute([$kost['user_id']]);
    $owner = $oS->fetch();
}
?>
<div class="detail-kost">
    <!-- Flash (bila ada) -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="detail-header">
        <!-- Hero image (utama) -->
        <?php if (!empty($kost['gambar'])): ?>
            <div class="detail-image-wrapper">
                <img src="<?php echo Storage::url('kost', $kost['gambar']); ?>" alt="<?php echo htmlspecialchars($kost['nama']); ?>" class="detail-image">
            </div>
        <?php endif; ?>

        <h1>🏠 <?php echo htmlspecialchars($kost['nama']); ?></h1>
        <p style="color:#64748b; font-size:16px;">📍 <?php echo htmlspecialchars($kost['lokasi']); ?></p>
        <div class="detail-price">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?> <small style="font-size:16px;color:#64748b;font-weight:400;">/ bulan</small></div>

        <div style="margin:20px 0; padding:16px 0; border-top:1px solid #f1f5f9; border-bottom:1px solid #f1f5f9;">
            <span class="badge badge-info" style="font-size:14px;"><?php echo ucfirst($kost['jenis_kost']); ?></span>
            <span class="badge badge-success" style="font-size:14px;">Sisa <?php echo $kost['kamar_tersedia']; ?> kamar</span>
        </div>

        <?php if (!empty($kost['deskripsi'])): ?>
            <p style="color:#475569; line-height:1.8; font-size:15px;"><?php echo nl2br(htmlspecialchars($kost['deskripsi'])); ?></p>
        <?php endif; ?>

        <!-- Galeri foto -->
        <?php if (count($semuaFoto) > 1): ?>
            <h4 style="margin-top:24px; font-size:16px; font-weight:700;">📸 Galeri Foto</h4>
            <div class="gallery-grid">
                <?php foreach ($semuaFoto as $i => $f): ?>
                    <?php if ($i === 0) continue; // foto utama sudah tampil di hero ?>
                    <img src="<?php echo Storage::url('kost', $f); ?>" alt="Foto galeri <?php echo $i; ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Ikon fasilitas -->
        <h4 style="margin-top:24px; font-size:16px; font-weight:700;">Fasilitas:</h4>
        <div class="card-fasilitas" style="margin-top:8px;">
            <?php
            $fas = explode(',', $kost['fasilitas']);
            $ikonFas = [
                'WiFi' => '📶', 'AC' => '❄️', 'Kamar Mandi Dalam' => '🚿',
                'Kamar Mandi Luar' => '🚿', 'Dapur' => '🍳', 'Dapur Bersama' => '🍳',
                'Parkir' => '🅿️', 'Parkir Motor' => '🛵', 'Listrik' => '💡',
                'Air' => '💧', 'Kipas Angin' => '🌀', 'Keamanan' => '🛡️',
                'TV' => '📺', 'Lemari' => '🗄️', 'Kasur' => '🛏️', 'Meja' => '🪑',
                'Kulkas' => '🧊', 'Mesin Cuci' => '🧺', 'Balkon' => '🌤️',
            ];
            foreach ($fas as $f) {
                $f = trim($f);
                if ($f === '') continue;
                $ikon = $ikonFas[$f] ?? '✅';
                echo '<span>' . $ikon . ' ' . htmlspecialchars($f) . '</span>';
            }
            ?>
        </div>

        <!-- Aturan kost -->
        <?php if (!empty($kost['aturan'])): ?>
            <h4 style="margin-top:24px; font-size:16px; font-weight:700;">📋 Aturan Kost</h4>
            <ul style="margin-top:8px; padding-left:20px; color:#475569; line-height:1.9;">
                <?php
                $aturanList = preg_split('/\r\n|\r|\n/', trim($kost['aturan']));
                foreach ($aturanList as $a) {
                    $a = trim($a);
                    if ($a !== '') echo '<li>' . htmlspecialchars($a) . '</li>';
                }
                ?>
            </ul>
        <?php endif; ?>

        <!-- Info pemilik -->
        <?php if ($owner): ?>
            <h4 style="margin-top:24px; font-size:16px; font-weight:700;">🏪 Pemilik Kost</h4>
            <div style="margin-top:8px; color:#475569; line-height:1.8;">
                <div><strong><?php echo htmlspecialchars($owner['nama']); ?></strong></div>
                <?php if (!empty($owner['no_hp'])): ?>
                    <div>📞 <?php echo htmlspecialchars($owner['no_hp']); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Peta -->
        <?php if (!empty($kost['lokasi'])): ?>
            <h4 style="margin-top:24px; font-size:16px; font-weight:700;">🗺️ Lokasi</h4>
            <iframe class="map-embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                src="https://maps.google.com/maps?q=<?php echo urlencode($kost['lokasi']); ?>&output=embed"></iframe>
        <?php endif; ?>
    </div>

    <!-- Booking Form (khusus penyewa) -->
    <?php if ($_SESSION['role'] === 'penyewa'): ?>
    <div class="form-card" style="max-width:100%;">
        <h2>📅 Booking Kost Ini</h2>
        <form method="POST" action="pesan_kost.php?id=<?php echo $kost['id']; ?>">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label>Nama Kost</label>
                <input type="text" value="<?php echo htmlspecialchars($kost['nama']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Harga Sewa</label>
                <input type="text" value="Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?> / bulan" disabled>
            </div>
            <div class="form-group">
                <label for="tanggal_mulai">Tanggal Mulai Sewa</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Pesan Sekarang ✅</button>
        </form>
        <div style="margin-top:16px;">
            <a href="list_kost.php" class="btn btn-outline" style="width:100%;">← Kembali ke Daftar Kost</a>
        </div>
    </div>
    <?php else: ?>
    <div style="margin-top:20px;">
        <a href="list_kost.php" class="btn btn-outline btn-lg" style="width:100%;">← Kembali ke Daftar Kost</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>