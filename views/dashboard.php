<?php
session_start();
$page_title = "Dashboard";
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="dashboard-page">
    <div class="dashboard-header">
        <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
        <span class="user-badge"><?php echo $_SESSION['role'] === 'ibu_kost' ? '🏪 Pemilik Kost' : '🔍 Penyewa'; ?></span>
    </div>

    <?php if ($_SESSION['role'] === 'ibu_kost'): ?>
        <!-- MENU IBU KOST -->
        <?php
        // Hitung pemesanan baru (belum dibaca) untuk badge di kartu
        require_once '../config/database.php';
        require_once '../config/notifikasi.php';
        $notif_count = hitung_notifikasi_baru($conn, (int)$_SESSION['user_id']);
        ?>
        <div class="dashboard-menu">
            <a href="tambah_kost.php" class="dashboard-card">
                <div class="dash-icon">➕</div>
                <h4>Tambah Kost</h4>
            </a>
            <a href="kelola_kost.php" class="dashboard-card">
                <div class="dash-icon">📋</div>
                <h4>Kelola Kost</h4>
            </a>
            <a href="daftar_pemesanan.php" class="dashboard-card" style="position:relative;">
                <?php if ($notif_count > 0): ?>
                <span class="dash-badge"><?php echo $notif_count > 99 ? '99+' : (int)$notif_count; ?></span>
                <?php endif; ?>
                <div class="dash-icon">📦</div>
                <h4>Pemesanan Masuk</h4>
            </a>
            <a href="list_kost.php" class="dashboard-card">
                <div class="dash-icon">👁️</div>
                <h4>Lihat Semua Kost</h4>
            </a>
        </div>

        <div class="table-wrapper mt-40">
            <h3>📊 Ringkasan Kost Kamu</h3>
            <?php
            require_once '../config/database.php';
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT id, nama, harga, kamar_tersedia FROM kost WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $kosts = $stmt->fetchAll();
            ?>
            <?php if (count($kosts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nama Kost</th>
                        <th>Harga</th>
                        <th>Kamar Tersedia</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kosts as $k): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($k['nama']); ?></strong></td>
                        <td>Rp <?php echo number_format($k['harga'], 0, ',', '.'); ?></td>
                        <td><?php echo $k['kamar_tersedia']; ?> kamar</td>
                        <td>
                            <a href="edit_kost.php?id=<?php echo $k['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="kelola_kost.php?hapus=<?php echo $k['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus kost ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="color:#64748b;">Belum ada kost. <a href="tambah_kost.php">Tambah kost sekarang</a> 🏠</p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- MENU PENYEWA -->
        <div class="dashboard-menu">
            <a href="list_kost.php" class="dashboard-card">
                <div class="dash-icon">🏠</div>
                <h4>Cari Kost</h4>
            </a>
            <a href="riwayat_booking.php" class="dashboard-card">
                <div class="dash-icon">📋</div>
                <h4>Riwayat Booking</h4>
            </a>
            <a href="../index.php" class="dashboard-card">
                <div class="dash-icon">🏡</div>
                <h4>Beranda</h4>
            </a>
        </div>

        <!-- RECOMENDED KOST -->
        <div class="mt-40">
            <h3 style="font-size:22px; font-weight:700; margin-bottom:20px;">🏆 Rekomendasi Kost</h3>
            <?php
            require_once '../config/database.php';
            $result = $conn->query("SELECT * FROM kost ORDER BY created_at DESC LIMIT 3");
            $kosts = $result ? $result->fetchAll() : [];
            if (count($kosts) > 0):
            ?>
            <div class="kost-grid">
                <?php foreach ($kosts as $kost): ?>
                <div class="kost-card">
                    <div class="card-img" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <span>🏠</span>
                        <span class="card-tag"><?php echo ucfirst($kost['jenis_kost']); ?></span>
                        <span class="card-price-tag">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?>/bln</span>
                    </div>
                    <div class="card-body">
                        <h3><?php echo htmlspecialchars($kost['nama']); ?></h3>
                        <div class="card-location">📍 <?php echo htmlspecialchars($kost['lokasi']); ?></div>
                        <div class="card-footer">
                            <span class="price">Rp <?php echo number_format($kost['harga'], 0, ',', '.'); ?> <small>/bulan</small></span>
                            <a href="detail_kost.php?id=<?php echo $kost['id']; ?>" class="btn btn-primary btn-sm">Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="color:#64748b;">Belum ada kost tersedia saat ini.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
